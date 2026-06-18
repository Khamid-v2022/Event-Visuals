<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventInterest;
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

    private const FILTER_STATUSES = ['all', ...self::EVENT_STATUSES];

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

    private const CALENDAR_TTL_SECONDS = 60 * 10;

    public function __construct(private GeocodingService $geocoding) {}

    public function calendarData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'location' => 'nullable|string|max:200',
            'q' => 'nullable|string|max:200',
            'type' => 'nullable|string|in:'.implode(',', self::EVENT_TYPES),
            'status' => 'nullable|string|in:'.implode(',', self::FILTER_STATUSES),
            'sort' => 'nullable|string|in:'.implode(',', self::SORT_OPTIONS),
            'booked_only' => 'sometimes|boolean',
            'interested_only' => 'sometimes|boolean',
        ]);

        $validated['sort'] = $validated['sort'] ?? 'recent';

        $cached = $this->isUserSpecificListFilter($validated)
            ? $this->fetchCalendarMonth($validated)
            : Cache::remember(
                'events.visual2.calendar.v2:'.md5(json_encode(Arr::sortRecursive($validated))),
                self::CALENDAR_TTL_SECONDS,
                fn () => $this->fetchCalendarMonth($validated),
            );

        $events = Event::hydrate($cached['events'] ?? [])->all();

        return response()->json([
            'data' => $this->transformPage($events),
            'total' => $cached['total'],
        ]);
    }

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
            'status' => 'nullable|string|in:'.implode(',', self::FILTER_STATUSES),
            'sort' => 'nullable|string|in:'.implode(',', self::SORT_OPTIONS),
            'booked_only' => 'sometimes|boolean',
            'interested_only' => 'sometimes|boolean',
        ]);

        $validated['per_page'] = min((int) ($validated['per_page'] ?? self::PER_PAGE_DEFAULT), self::PER_PAGE_MAX);
        $validated['sort'] = $validated['sort'] ?? 'recent';
        $page = (int) ($validated['page'] ?? 1);
        $validated['offset'] = array_key_exists('offset', $validated)
            ? (int) $validated['offset']
            : ($page - 1) * $validated['per_page'];

        $cached = $this->isUserSpecificListFilter($validated)
            ? $this->fetchGridPage($validated, $page)
            : Cache::remember(
                'events.visual1.grid.v3:'.md5(json_encode(Arr::sortRecursive($validated))),
                self::GRID_TTL_SECONDS,
                fn () => $this->fetchGridPage($validated, $page),
            );

        // Per-user flags — apply after cache, never inside it.
        $events = Event::hydrate($cached['events'] ?? [])->all();

        return response()->json([
            'data' => $this->transformPage($events),
            'current_page' => $cached['current_page'],
            'has_more' => $cached['has_more'],
            'total' => $cached['total'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{events: array<int, array<string, mixed>>, total: int}
     */
    private function fetchCalendarMonth(array $validated): array
    {
        $gridRange = $this->calendarGridRange($validated['month']);
        $query = $this->buildCalendarFilteredQuery($validated, $gridRange);
        $this->applyCalendarSort($query, $validated['sort']);

        $rows = $query->get(['id', 'type', 'status', 'created_time', 'latitude', 'longitude', 'payload']);

        return [
            'events' => $rows->map(fn (Event $event) => $event->getAttributes())->values()->all(),
            'total' => $rows->count(),
        ];
    }

    /**
     * Six-week Sunday-start grid covering the requested month.
     *
     * @return array{from_ts: int, to_ts: int}
     */
    private function calendarGridRange(string $month): array
    {
        $firstOfMonth = \DateTimeImmutable::createFromFormat('Y-m-d', $month.'-01');
        $weekday = (int) $firstOfMonth->format('w');
        $gridStart = $firstOfMonth->modify("-{$weekday} days")->setTime(0, 0, 0);
        $gridEnd = $gridStart->modify('+41 days')->setTime(23, 59, 59);

        return [
            'from_ts' => $gridStart->getTimestamp(),
            'to_ts' => $gridEnd->getTimestamp(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{from_ts: int, to_ts: int}  $gridRange
     * @return Builder<Event>
     */
    private function buildCalendarFilteredQuery(array $filters, array $gridRange): Builder
    {
        $query = Event::query();

        $this->applyStatusFilter($query, $filters['status'] ?? null);
        $this->applyTypeFilter($query, $filters['type'] ?? null);
        $this->applyScheduleRangeFilter($query, $gridRange['from_ts'], $gridRange['to_ts']);
        $this->applyScheduleDateFilter($query, $filters);
        $this->applyLocationFilter($query, $filters['location'] ?? null);
        $this->applySearchFilter($query, $filters['q'] ?? null);
        $this->applyBookedFilter($query, filter_var($filters['booked_only'] ?? false, FILTER_VALIDATE_BOOLEAN));
        $this->applyInterestedFilter($query, filter_var($filters['interested_only'] ?? false, FILTER_VALIDATE_BOOLEAN));

        return $query;
    }

    /**
     * @param  Builder<Event>  $query
     */
    private function applyScheduleRangeFilter(Builder $query, int $fromTs, int $toTs): void
    {
        $query->whereRaw(
            'CAST(json_extract(payload, \'$.schedule.starts_at\') AS INTEGER) BETWEEN ? AND ?',
            [$fromTs, $toTs],
        );
    }

    /**
     * Optional user date filters — applied to event start time, not created_time.
     *
     * @param  Builder<Event>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyScheduleDateFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereRaw(
                'CAST(json_extract(payload, \'$.schedule.starts_at\') AS INTEGER) >= ?',
                [strtotime($filters['date_from'].' 00:00:00 UTC')],
            );
        }

        if (! empty($filters['date_to'])) {
            $query->whereRaw(
                'CAST(json_extract(payload, \'$.schedule.starts_at\') AS INTEGER) <= ?',
                [strtotime($filters['date_to'].' 23:59:59 UTC')],
            );
        }
    }

    /**
     * @param  Builder<Event>  $query
     */
    private function applyCalendarSort(Builder $query, string $sort): void
    {
        if ($sort === 'price_asc') {
            if ($this->hasMinPriceColumn()) {
                $query->orderBy('min_price');
            } else {
                $query->orderByRaw("CAST(json_extract(payload, '$.pricing.min_price') AS REAL) ASC");
            }

            $query->orderByRaw("CAST(json_extract(payload, '$.schedule.starts_at') AS INTEGER) ASC");

            return;
        }

        if ($sort === 'price_desc') {
            if ($this->hasMinPriceColumn()) {
                $query->orderByDesc('min_price');
            } else {
                $query->orderByRaw("CAST(json_extract(payload, '$.pricing.min_price') AS REAL) DESC");
            }

            $query->orderByRaw("CAST(json_extract(payload, '$.schedule.starts_at') AS INTEGER) ASC");

            return;
        }

        $query->orderByRaw("CAST(json_extract(payload, '$.schedule.starts_at') AS INTEGER) ASC");
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{events: array<int, array<string, mixed>>, current_page: int, has_more: bool, total: int|null}
     */
    private function fetchGridPage(array $validated, int $page): array
    {
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
            // Store plain rows — serializing Eloquent models breaks on cache read.
            'events' => $rows->take($limit)->map(fn (Event $event) => $event->getAttributes())->values()->all(),
            'current_page' => $page,
            'has_more' => $hasMore,
            'total' => $total,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function isUserSpecificListFilter(array $filters): bool
    {
        return $this->isBookedOnlyFilter($filters) || $this->isInterestedOnlyFilter($filters);
    }

    private function isBookedOnlyFilter(array $filters): bool
    {
        return filter_var($filters['booked_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function isInterestedOnlyFilter(array $filters): bool
    {
        return filter_var($filters['interested_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
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
        $this->applyBookedFilter($query, filter_var($filters['booked_only'] ?? false, FILTER_VALIDATE_BOOLEAN));
        $this->applyInterestedFilter($query, filter_var($filters['interested_only'] ?? false, FILTER_VALIDATE_BOOLEAN));

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolveTotal(array $filters): int
    {
        if ($this->isUserSpecificListFilter($filters)) {
            return $this->buildFilteredQuery($filters)->count();
        }

        $countKey = 'events.visual1.total.v3:'.md5(json_encode(Arr::sortRecursive(Arr::except($filters, ['page']))));

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
        $eventIds = collect($events)->pluck('id')->all();
        $bookedIds = $this->bookedEventIds($eventIds);
        $interestedIds = $this->interestedEventIds($eventIds);

        return collect($events)
            ->map(fn (Event $event) => [
                ...$this->transform($event),
                'booked' => isset($bookedIds[$event->id]),
                'interested' => isset($interestedIds[$event->id]),
            ])
            ->all();
    }

    /**
     * @param  array<int, string>  $eventIds
     * @return array<string, true>
     */
    private function interestedEventIds(array $eventIds): array
    {
        $user = auth()->user();

        if ($user === null || $eventIds === []) {
            return [];
        }

        return EventInterest::query()
            ->where('user_id', $user->id)
            ->whereIn('event_id', $eventIds)
            ->pluck('event_id')
            ->mapWithKeys(fn (string $id) => [$id => true])
            ->all();
    }

    /**
     * @param  Builder<Event>  $query
     */
    private function applyInterestedFilter(Builder $query, bool $interestedOnly): void
    {
        if (! $interestedOnly) {
            return;
        }

        $user = auth()->user();

        if ($user === null) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->whereIn('events.id', EventInterest::query()
            ->where('user_id', $user->id)
            ->select('event_id'));
    }

    /**
     * @param  array<int, string>  $eventIds
     * @return array<string, true>
     */
    private function bookedEventIds(array $eventIds): array
    {
        $user = auth()->user();

        if ($user === null || $eventIds === []) {
            return [];
        }

        return EventAttendance::query()
            ->where('user_id', $user->id)
            ->whereIn('event_id', $eventIds)
            ->pluck('event_id')
            ->mapWithKeys(fn (string $id) => [$id => true])
            ->all();
    }

    /**
     * @param  Builder<Event>  $query
     */
    private function applyBookedFilter(Builder $query, bool $bookedOnly): void
    {
        if (! $bookedOnly) {
            return;
        }

        $user = auth()->user();

        if ($user === null) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->whereIn('events.id', EventAttendance::query()
            ->where('user_id', $user->id)
            ->select('event_id'));
    }

    /**
     * @param  Builder<Event>  $query
     */
    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        $status = $status !== null ? trim($status) : '';

        if ($status === '' || $status === 'all') {
            return;
        }

        $query->where('status', $status);
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
