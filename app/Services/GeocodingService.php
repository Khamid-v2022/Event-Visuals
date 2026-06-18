<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Resolves coordinates to addresses (and back) via OpenStreetMap Nominatim.
 * Results are cached aggressively because the seeded dataset reuses city anchors.
 */
class GeocodingService
{
    private const BASE_URL = 'https://nominatim.openstreetmap.org';

    private const REVERSE_TTL_SECONDS = 60 * 60 * 24 * 30;

    private const FORWARD_TTL_SECONDS = 60 * 60 * 24 * 7;

    private const REVERSE_MISS_TTL_SECONDS = 60 * 60;

    /** Default search radius when filtering by a place name. */
    private const DEFAULT_RADIUS_KM = 50;

    /**
     * Reverse geocode coordinates to a human-readable address via Nominatim.
     * Results are cached to avoid repeat API calls.
     */
    public function reverse(float $latitude, float $longitude): ?string
    {
        $cacheKey = $this->reverseCacheKey($latitude, $longitude);

        return Cache::remember($cacheKey, self::REVERSE_TTL_SECONDS, function () use ($latitude, $longitude) {
            $address = $this->fetchReverse($latitude, $longitude);

            if ($address === null) {
                Cache::put($this->reverseCacheKey($latitude, $longitude), '', self::REVERSE_MISS_TTL_SECONDS);

                return null;
            }

            return $address;
        });
    }

    private function fetchReverse(float $latitude, float $longitude): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent(),
            ])->timeout(3)->connectTimeout(2)->get(self::BASE_URL.'/reverse', [
                'format' => 'json',
                'lat' => $latitude,
                'lon' => $longitude,
                'zoom' => 14,
                'addressdetails' => 0,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $display = $response->json('display_name');

            return is_string($display) ? $display : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function forward(string $query): ?array
    {
        $normalized = trim(mb_strtolower($query));
        if ($normalized === '') {
            return null;
        }

        $cacheKey = 'geocode:forward:'.md5($normalized);

        return Cache::remember($cacheKey, self::FORWARD_TTL_SECONDS, function () use ($query) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->userAgent(),
                ])->timeout(3)->connectTimeout(2)->get(self::BASE_URL.'/search', [
                    'format' => 'json',
                    'q' => $query,
                    'limit' => 1,
                ]);

                if (! $response->successful()) {
                    return null;
                }

                $first = $response->json('0');
                if (! is_array($first) || ! isset($first['lat'], $first['lon'])) {
                    return null;
                }

                return [
                    'lat' => (float) $first['lat'],
                    'lng' => (float) $first['lon'],
                ];
            } catch (Throwable) {
                return null;
            }
        });
    }

    /**
     * @return Collection<int, array{label: string, lat: float, lng: float}>
     */
    public function suggest(string $query, int $limit = 5): Collection
    {
        $normalized = trim(mb_strtolower($query));
        if ($normalized === '') {
            return collect();
        }

        $cacheKey = 'geocode:suggest:'.$limit.':'.md5($normalized);

        return Cache::remember($cacheKey, self::FORWARD_TTL_SECONDS, function () use ($query, $limit) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->userAgent(),
                ])->timeout(3)->connectTimeout(2)->get(self::BASE_URL.'/search', [
                    'format' => 'json',
                    'q' => $query,
                    'limit' => $limit,
                ]);

                if (! $response->successful()) {
                    return collect();
                }

                return collect($response->json())
                    ->filter(fn (mixed $item) => is_array($item) && isset($item['display_name'], $item['lat'], $item['lon']))
                    ->map(fn (array $item) => [
                        'label' => (string) $item['display_name'],
                        'lat' => (float) $item['lat'],
                        'lng' => (float) $item['lon'],
                    ])
                    ->values();
            } catch (Throwable) {
                return collect();
            }
        });
    }

    /**
     * @param  Builder<Event>  $query
     */
    public function applyProximityFilter(Builder $query, float $latitude, float $longitude, float $radiusKm = self::DEFAULT_RADIUS_KM): void
    {
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / (111.0 * max(cos(deg2rad($latitude)), 0.01));

        $query->whereBetween('latitude', [$latitude - $latDelta, $latitude + $latDelta])
            ->whereBetween('longitude', [$longitude - $lngDelta, $longitude + $lngDelta]);
    }

    private function reverseCacheKey(float $latitude, float $longitude): string
    {
        return sprintf('geocode:reverse:%.1f:%.1f', $latitude, $longitude);
    }

    private function userAgent(): string
    {
        return config('app.name', 'Laravel').' Event Visuals (coding-test)';
    }
}
