<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\GeocodingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class EventVisualController extends Controller
{
    private const EVENT_TYPES = [
        'concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition',
    ];

    private const EVENT_STATUSES = ['draft', 'published', 'cancelled', 'sold_out'];
    /** Alternate between two local image sets so adjacent cards feel less repetitive. */
    private const EVENT_IMAGE_SETS = [
        [
            '/imgs/events/event1-1.png',
            '/imgs/events/event1-2.png',
            '/imgs/events/event1-3.png',
        ],
        [
            '/imgs/events/event2-1.png',
            '/imgs/events/event2-2.png',
            '/imgs/events/event2-3.png',
        ],
    ];

    private const GRID_TTL_SECONDS = 60 * 5;

    public function __construct(private GeocodingService $geocoding) {}

    public function gridData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'location' => 'nullable|string|max:200',
            'q' => 'nullable|string|max:200',
            'type' => 'nullable|string|in:'.implode(',', self::EVENT_TYPES),
            'status' => 'nullable|string|in:'.implode(',', self::EVENT_STATUSES),
        ]);

        $cacheKey = 'events.visual1.grid:'.md5(json_encode(Arr::sortRecursive($validated)));

        $payload = Cache::remember($cacheKey, self::GRID_TTL_SECONDS, function () use ($validated) {
            $query = Event::query();

            $this->applyStatusFilter($query, $validated['status'] ?? null);
            $this->applyTypeFilter($query, $validated['type'] ?? null);
            $this->applyDateFilter($query, $validated);
            $this->applyLocationFilter($query, $validated['location'] ?? null);
            $this->applySearchFilter($query, $validated['q'] ?? null);

            $events = $query
                ->orderBy('created_time')
                ->paginate(12, ['*'], 'page', (int) ($validated['page'] ?? 1))
                ->withQueryString();

            return [
                'data' => collect($events->items())->map(fn (Event $event) => $this->transform($event))->all(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'total' => $events->total(),
            ];
        });

        return response()->json($payload);
    }

    public function locationSuggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:200',
        ]);

        $query = trim((string) ($validated['query'] ?? ''));
        if (mb_strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $this->geocoding->suggest($query)->all(),
        ]);
    }

    /**
     * @param  Builder<Event>  $query
     */
    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        if ($status !== null && trim($status) !== '') {
            $query->where('status', $status);

            return;
        }

        $query->whereIn('status', ['published', 'sold_out']);
    }

    /**
     * @param  Builder<Event>  $query
     */
    private function applyTypeFilter(Builder $query, ?string $type): void
    {
        if ($type !== null && trim($type) !== '') {
            $query->where('type', $type);
        }
    }

    /**
     * @param  Builder<Event>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyDateFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->where('created_time', '>=', strtotime($filters['date_from'].' 00:00:00 UTC'));
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_time', '<=', strtotime($filters['date_to'].' 23:59:59 UTC'));
        }
    }

    /**
     * @param  Builder<Event>  $query
     */
    private function applyLocationFilter(Builder $query, ?string $location): void
    {
        if ($location === null || trim($location) === '') {
            return;
        }

        $coords = $this->geocoding->forward($location);
        if ($coords === null) {
            // Unknown place — return no rows instead of the full catalogue.
            $query->whereRaw('0 = 1');

            return;
        }

        $this->geocoding->applyProximityFilter($query, $coords['lat'], $coords['lng']);
    }

    /**
     * Text search across payload fields; also matches events near a geocoded place name.
     *
     * @param  Builder<Event>  $query
     */
    private function applySearchFilter(Builder $query, ?string $term): void
    {
        if ($term === null || trim($term) === '') {
            return;
        }

        $like = '%'.$this->escapeLike($term).'%';
        $coords = $this->geocoding->forward($term);

        $query->where(function (Builder $inner) use ($like, $coords) {
            $inner
                ->whereRaw("json_extract(payload, '$.name') LIKE ? ESCAPE '\\'", [$like])
                ->orWhereRaw("json_extract(payload, '$.description') LIKE ? ESCAPE '\\'", [$like])
                ->orWhereRaw("json_extract(payload, '$.venue.name') LIKE ? ESCAPE '\\'", [$like])
                ->orWhereRaw("json_extract(payload, '$.notes') LIKE ? ESCAPE '\\'", [$like])
                ->orWhereRaw("json_extract(payload, '$.tags') LIKE ? ESCAPE '\\'", [$like]);

            if ($coords !== null) {
                $inner->orWhere(function (Builder $proximity) use ($coords) {
                    $this->geocoding->applyProximityFilter($proximity, $coords['lat'], $coords['lng']);
                });
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Event $event): array
    {
        $payload = $event->payload ?? [];
        $latitude = $event->latitude ?? (float) ($payload['location']['lat'] ?? 0);
        $longitude = $event->longitude ?? (float) ($payload['location']['lng'] ?? 0);

        $address = null;
        if ($latitude !== 0.0 || $longitude !== 0.0) {
            $address = $this->geocoding->reverse($latitude, $longitude)
                ?? sprintf('%.4f, %.4f', $latitude, $longitude);
        }

        return [
            'id' => $event->id,
            'type' => $event->type,
            'status' => $event->status,
            'name' => $payload['name'] ?? 'Untitled Event',
            'description' => $payload['description'] ?? '',
            'notes' => $payload['notes'] ?? '',
            'organizer' => $payload['organizer'] ?? null,
            'venue' => $payload['venue'] ?? null,
            'schedule' => [
                'starts_at' => (int) ($payload['schedule']['starts_at'] ?? $event->created_time ?? 0),
                'ends_at' => (int) ($payload['schedule']['ends_at'] ?? 0),
            ],
            'pricing' => $payload['pricing'] ?? null,
            'tags' => $payload['tags'] ?? [],
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'images' => $this->resolveImages($event),
        ];
    }

    /**
     * Use a stable hash so the same event always keeps the same image set.
     *
     * @return array<int, string>
     */
    private function resolveImages(Event $event): array
    {
        $index = crc32($event->id) % count(self::EVENT_IMAGE_SETS);

        return self::EVENT_IMAGE_SETS[$index];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
