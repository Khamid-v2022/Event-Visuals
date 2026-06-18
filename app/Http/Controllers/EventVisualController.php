<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\GeocodingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class EventVisualController extends Controller
{
    private const EVENT_TYPES = [
        'concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition',
    ];

    private const EVENT_STATUSES = ['draft', 'published', 'cancelled', 'sold_out'];

    private const SORT_OPTIONS = ['recent', 'price_asc', 'price_desc'];

    private const PER_PAGE_MAX = 48;

    private const PER_PAGE_DEFAULT = 48;

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

    private const GRID_TTL_SECONDS = 60 * 10;

    private const TOTAL_TTL_SECONDS = 60 * 15;

    public function __construct(private GeocodingService $geocoding) {}

    public function gridData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:'.self::PER_PAGE_MAX,
            'offset' => 'sometimes|integer|min:0',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'location' => 'nullable|string|max:200',
            'q' => 'nullable|string|max:200',
            'type' => 'nullable|string|in:'.implode(',', self::EVENT_TYPES),
            'status' => 'nullable|string|in:'.implode(',', self::EVENT_STATUSES),
            'sort' => 'nullable|string|in:'.implode(',', self::SORT_OPTIONS),
        ]);

        $validated['per_page'] = min((int) ($validated['per_page'] ?? self::PER_PAGE_DEFAULT), self::PER_PAGE_MAX);
        $validated['sort'] = $validated['sort'] ?? 'recent';
        $page = (int) ($validated['page'] ?? 1);
        $validated['offset'] = array_key_exists('offset', $validated)
            ? (int) $validated['offset']
            : ($page - 1) * $validated['per_page'];

        $cacheKey = 'events.visual1.grid:'.md5(json_encode(Arr::sortRecursive($validated)));

        $payload = Cache::remember($cacheKey, self::GRID_TTL_SECONDS, function () use ($validated, $page) {
            $query = $this->buildFilteredQuery($validated);
            $this->applySort($query, $validated['sort']);

            $limit = $validated['per_page'];
            $offset = $validated['offset'];

            // Fetch one extra row to detect whether another page exists.
            $rows = $query
                ->skip($offset)
                ->take($limit + 1)
                ->get(['id', 'type', 'status', 'created_time', 'latitude', 'longitude', 'payload']);

            $hasMore = $rows->count() > $limit;

            $total = $page === 1
                ? $this->resolveTotal($validated)
                : null;

            return [
                'data' => $this->transformPage($rows->take($limit)->all()),
                'current_page' => $page,
                'has_more' => $hasMore,
                'total' => $total,
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

    public function resolveAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $latitude = (float) $validated['lat'];
        $longitude = (float) $validated['lng'];

        if ($latitude === 0.0 && $longitude === 0.0) {
            return response()->json(['address' => null]);
        }

        return response()->json([
            'address' => $this->geocoding->reverse($latitude, $longitude),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Event>
     */
    private function buildFilteredQuery(array $filters): Builder
    {
        $query = Event::query();

        $this->applyStatusFilter($query, $filters['status'] ?? null);
        $this->applyTypeFilter($query, $filters['type'] ?? null);
        $this->applyDateFilter($query, $filters);
        $this->applyLocationFilter($query, $filters['location'] ?? null);
        $this->applySearchFilter($query, $filters['q'] ?? null);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolveTotal(array $filters): int
    {
        $countKey = 'events.visual1.total:'.md5(json_encode(Arr::sortRecursive(Arr::except($filters, ['page']))));

        return (int) Cache::remember($countKey, self::TOTAL_TTL_SECONDS, function () use ($filters) {
            return $this->buildFilteredQuery($filters)->count();
        });
    }

    /**
     * @param  Builder<Event>  $query
     */
    private function applySort(Builder $query, string $sort): void
    {
        if ($sort === 'price_asc') {
            if ($this->hasMinPriceColumn()) {
                $query->orderBy('min_price')->orderByDesc('created_time');
            } else {
                $query->orderByRaw("CAST(json_extract(payload, '$.pricing.min_price') AS REAL) ASC")
                    ->orderByDesc('created_time');
            }

            return;
        }

        if ($sort === 'price_desc') {
            if ($this->hasMinPriceColumn()) {
                $query->orderByDesc('min_price')->orderByDesc('created_time');
            } else {
                $query->orderByRaw("CAST(json_extract(payload, '$.pricing.min_price') AS REAL) DESC")
                    ->orderByDesc('created_time');
            }

            return;
        }

        $query->orderByDesc('created_time');
    }

    /**
     * @param  array<int, Event>  $events
     * @return array<int, array<string, mixed>>
     */
    private function transformPage(array $events): array
    {
        return collect($events)->map(fn (Event $event) => $this->transform($event))->all();
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

        // Forward-geocode the place name, then filter by proximity.
        $coords = $this->geocoding->forward($location);
        if ($coords === null) {
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
            'latitude' => $latitude,
            'longitude' => $longitude,
            'images' => $this->resolveImages($event),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function resolveImages(Event $event): array
    {
        $index = crc32($event->id) % count(self::EVENT_IMAGE_SETS);

        return self::EVENT_IMAGE_SETS[$index];
    }

    private function hasMinPriceColumn(): bool
    {
        return Cache::rememberForever('events.has_min_price_column', fn () => Schema::hasColumn('events', 'min_price'));
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
