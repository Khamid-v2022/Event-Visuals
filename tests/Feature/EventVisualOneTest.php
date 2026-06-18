<?php

use App\Jobs\SendEventAttendanceConfirmation;
use App\Jobs\SendEventAttendanceReminder;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('returns paginated visual grid data with transformed events', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'type' => 'meetup',
        'status' => 'published',
        'created_time' => 1_813_294_061,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'payload' => [
            'name' => 'Summer Climate Night',
            'category' => 'meetup',
            'description' => 'Join us for Summer Climate Night.',
            'organizer' => ['name' => 'Organizer 329', 'verified' => true],
            'venue' => ['name' => 'The Grand Rooftop', 'capacity' => '8431'],
            'location' => ['lat' => '40.7128', 'lng' => '-74.0060'],
            'schedule' => ['starts_at' => '1813294061', 'ends_at' => '1813360585'],
            'pricing' => ['currency' => 'USD', 'min_price' => '226.74'],
            'tags' => ['live', 'in-person'],
            'notes' => 'Extra details here.',
        ],
    ]);

    $this->getJson(route('events.visual1.data'))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.name', 'Summer Climate Night')
        ->assertJsonPath('data.0.latitude', 40.7128)
        ->assertJsonPath('data.0.longitude', -74.006)
        ->assertJsonCount(3, 'data.0.images')
        ->assertJsonStructure(['has_more']);
});

it('returns all events when status filter is all', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'status' => 'draft',
        'payload' => ['name' => 'Draft Event', 'description' => ''],
    ]);
    Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => ['name' => 'Published Event', 'description' => ''],
    ]);

    $this->getJson(route('events.visual1.data', ['status' => 'all']))
        ->assertOk()
        ->assertJsonPath('total', 2);
});

it('serves cached grid data without error on repeated requests', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->count(3)->create([
        'status' => 'published',
        'payload' => ['name' => 'Cached Event', 'description' => ''],
    ]);

    $url = route('events.visual1.data', ['page' => 1, 'per_page' => 48, 'offset' => 0, 'sort' => 'recent']);

    $this->getJson($url)->assertOk()->assertJsonCount(3, 'data');
    $this->getJson($url)->assertOk()->assertJsonCount(3, 'data');
});

it('sorts visual grid data by price ascending', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => 1_700_000_000,
        'payload' => [
            'name' => 'Cheap Event',
            'pricing' => ['currency' => 'USD', 'min_price' => '10.00'],
        ],
    ]);
    Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => 1_700_000_100,
        'payload' => [
            'name' => 'Premium Event',
            'pricing' => ['currency' => 'USD', 'min_price' => '250.00'],
        ],
    ]);

    $this->getJson(route('events.visual1.data', ['sort' => 'price_asc']))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Cheap Event');
});

it('filters visual grid data by search term', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => ['name' => 'Unique Jazz Gala', 'description' => ''],
    ]);
    Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => ['name' => 'Other Event', 'description' => ''],
    ]);

    $this->getJson(route('events.visual1.data', ['q' => 'Jazz']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.name', 'Unique Jazz Gala');
});

it('filters visual grid data by type and status', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'type' => 'concert',
        'status' => 'published',
        'payload' => ['name' => 'Concert A', 'description' => ''],
    ]);
    Event::factory()->for($user)->create([
        'type' => 'meetup',
        'status' => 'published',
        'payload' => ['name' => 'Meetup B', 'description' => ''],
    ]);

    $this->getJson(route('events.visual1.data', ['type' => 'concert']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.type', 'concert');
});

it('returns location suggestions for the location filter', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            [
                'display_name' => 'Copenhagen, Denmark',
                'lat' => '55.6761',
                'lon' => '12.5683',
            ],
            [
                'display_name' => 'Copenhagen Municipality, Denmark',
                'lat' => '55.6867',
                'lon' => '12.5701',
            ],
        ], 200),
    ]);

    $this->getJson(route('events.visual1.locations', ['query' => 'Copen']))
        ->assertOk()
        ->assertJsonPath('data.0.label', 'Copenhagen, Denmark')
        ->assertJsonPath('data.0.lat', 55.6761)
        ->assertJsonCount(2, 'data');
});

it('resolves a human-readable address for event detail', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            'display_name' => 'New York, United States',
        ], 200),
    ]);

    $this->getJson(route('events.visual1.address', ['lat' => 40.7128, 'lng' => -74.0060]))
        ->assertOk()
        ->assertJsonPath('address', 'New York, United States');
});

it('returns non-overlapping events when the first page is larger than later pages', function () {
    $user = User::factory()->create();

    for ($index = 1; $index <= 80; $index++) {
        Event::factory()->for($user)->create([
            'status' => 'published',
            'created_time' => 1_700_000_000 + $index,
            'payload' => ['name' => "Event {$index}", 'description' => ''],
        ]);
    }

    $pageOneIds = collect($this->getJson(route('events.visual1.data', [
        'page' => 1,
        'per_page' => 48,
        'offset' => 0,
    ]))->json('data'))->pluck('id');

    $pageTwoIds = collect($this->getJson(route('events.visual1.data', [
        'page' => 2,
        'per_page' => 24,
        'offset' => 48,
    ]))->json('data'))->pluck('id');

    expect($pageOneIds)->toHaveCount(48);
    expect($pageTwoIds)->toHaveCount(24);
    expect($pageOneIds->intersect($pageTwoIds))->toBeEmpty();
});

it('marks events as interested for authenticated users', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $event = Event::factory()->for($owner)->create([
        'status' => 'published',
        'payload' => ['name' => 'Saved Event', 'description' => ''],
    ]);

    $this->getJson(route('events.visual1.data'))
        ->assertJsonPath('data.0.interested', false);

    $this->actingAs($user)
        ->postJson(route('events.visual1.interests.store', $event))
        ->assertOk()
        ->assertJsonPath('interested', true);

    $this->actingAs($user)
        ->getJson(route('events.visual1.data'))
        ->assertOk()
        ->assertJsonPath('data.0.interested', true);
});

it('filters the grid to interested events only', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();

    $interested = Event::factory()->for($owner)->create([
        'status' => 'published',
        'payload' => ['name' => 'Interested Event', 'description' => ''],
    ]);
    Event::factory()->for($owner)->create([
        'status' => 'published',
        'payload' => ['name' => 'Other Event', 'description' => ''],
    ]);

    $this->actingAs($user)
        ->postJson(route('events.visual1.interests.store', $interested));

    $this->actingAs($user)
        ->getJson(route('events.visual1.data', ['interested_only' => true]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.name', 'Interested Event');
});

it('marks events as booked for authenticated users', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $event = Event::factory()->for($owner)->create([
        'status' => 'published',
        'payload' => ['name' => 'Saved Event', 'description' => ''],
    ]);

    // Warm the grid cache as a guest before login.
    $this->getJson(route('events.visual1.data'))
        ->assertJsonPath('data.0.booked', false);

    Queue::fake();

    $this->actingAs($user)
        ->postJson(route('events.visual1.attendances.store', $event))
        ->assertOk()
        ->assertJsonPath('booked', true);

    Queue::assertPushed(SendEventAttendanceConfirmation::class);

    $this->actingAs($user)
        ->getJson(route('events.visual1.data'))
        ->assertOk()
        ->assertJsonPath('data.0.booked', true);
});

it('filters the grid to booked events only', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();

    $booked = Event::factory()->for($owner)->create([
        'status' => 'published',
        'payload' => ['name' => 'Booked Event', 'description' => ''],
    ]);
    Event::factory()->for($owner)->create([
        'status' => 'published',
        'payload' => ['name' => 'Other Event', 'description' => ''],
    ]);

    Queue::fake();

    $this->actingAs($user)
        ->postJson(route('events.visual1.attendances.store', $booked));

    $this->actingAs($user)
        ->getJson(route('events.visual1.data', ['booked_only' => true]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.name', 'Booked Event');
});

it('reflects a newly added booking when booked only was previously empty', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $event = Event::factory()->for($owner)->create([
        'status' => 'published',
        'payload' => ['name' => 'Fresh Booking', 'description' => ''],
    ]);

    $bookedOnlyUrl = route('events.visual1.data', ['booked_only' => true]);

    $this->actingAs($user)
        ->getJson($bookedOnlyUrl)
        ->assertOk()
        ->assertJsonPath('total', 0);

    Queue::fake();

    $this->actingAs($user)
        ->postJson(route('events.visual1.attendances.store', $event))
        ->assertOk();

    $this->actingAs($user)
        ->getJson($bookedOnlyUrl)
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.name', 'Fresh Booking');
});

it('removes a booked event', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $event = Event::factory()->for($owner)->create([
        'status' => 'published',
        'payload' => ['name' => 'Saved Event', 'description' => ''],
    ]);

    Queue::fake();

    $this->actingAs($user)
        ->postJson(route('events.visual1.attendances.store', $event));

    $this->actingAs($user)
        ->deleteJson(route('events.visual1.attendances.destroy', $event))
        ->assertOk()
        ->assertJsonPath('booked', false);

    $this->actingAs($user)
        ->getJson(route('events.visual1.data'))
        ->assertOk()
        ->assertJsonPath('data.0.booked', false);
});

it('does not re-dispatch confirmation when booking again', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => ['name' => 'Repeat Event', 'description' => ''],
    ]);

    Queue::fake();

    $this->actingAs($user)
        ->postJson(route('events.visual1.attendances.store', $event))
        ->assertOk();

    $this->actingAs($user)
        ->deleteJson(route('events.visual1.attendances.destroy', $event))
        ->assertOk();

    $this->actingAs($user)
        ->postJson(route('events.visual1.attendances.store', $event))
        ->assertOk();

    Queue::assertPushed(SendEventAttendanceConfirmation::class, 2);
});

it('dispatches reminder jobs for due attendances', function () {
    $user = User::factory()->create();
    $startsAt = now()->addDays(3)->subMinutes(30)->timestamp;
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => $startsAt,
        'payload' => [
            'name' => 'Soon Event',
            'description' => '',
            'schedule' => ['starts_at' => $startsAt, 'ends_at' => $startsAt + 3600],
        ],
    ]);

    EventAttendance::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
    ]);

    Queue::fake();

    Artisan::call('attendances:send-reminders');

    Queue::assertPushed(SendEventAttendanceReminder::class, function (SendEventAttendanceReminder $job) {
        return $job->window === 'three_days';
    });
});

it('does not dispatch reminders after unbooking', function () {
    $user = User::factory()->create();
    $startsAt = now()->addDays(3)->subMinutes(30)->timestamp;
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => $startsAt,
        'payload' => [
            'name' => 'Cancelled Event',
            'description' => '',
            'schedule' => ['starts_at' => $startsAt, 'ends_at' => $startsAt + 3600],
        ],
    ]);

    $attendance = EventAttendance::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
    ]);

    $this->actingAs($user)
        ->deleteJson(route('events.visual1.attendances.destroy', $event))
        ->assertOk();

    Queue::fake();

    Artisan::call('attendances:send-reminders');

    Queue::assertNotPushed(SendEventAttendanceReminder::class);
});

it('skips a queued reminder job when attendance was unbooked', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => ['name' => 'Gone Event', 'description' => ''],
    ]);

    $attendance = EventAttendance::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
    ]);

    $job = new SendEventAttendanceReminder($attendance, 'three_days');
    $attendance->delete();

    $job->handle();

    expect(EventAttendance::query()->count())->toBe(0);
});
