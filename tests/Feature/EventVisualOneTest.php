<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

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

    // Warm the grid cache as a guest before login.
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

it('reflects a newly added interest when interested only was previously empty', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $event = Event::factory()->for($owner)->create([
        'status' => 'published',
        'payload' => ['name' => 'Fresh Interest', 'description' => ''],
    ]);

    $interestedOnlyUrl = route('events.visual1.data', ['interested_only' => true]);

    // Warm an empty interested-only response (would be cached without the fix).
    $this->actingAs($user)
        ->getJson($interestedOnlyUrl)
        ->assertOk()
        ->assertJsonPath('total', 0);

    $this->actingAs($user)
        ->postJson(route('events.visual1.interests.store', $event))
        ->assertOk();

    $this->actingAs($user)
        ->getJson($interestedOnlyUrl)
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.name', 'Fresh Interest');
});

it('removes an interested event', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $event = Event::factory()->for($owner)->create([
        'status' => 'published',
        'payload' => ['name' => 'Saved Event', 'description' => ''],
    ]);

    $this->actingAs($user)
        ->postJson(route('events.visual1.interests.store', $event));

    $this->actingAs($user)
        ->deleteJson(route('events.visual1.interests.destroy', $event))
        ->assertOk()
        ->assertJsonPath('interested', false);

    $this->actingAs($user)
        ->getJson(route('events.visual1.data'))
        ->assertOk()
        ->assertJsonPath('data.0.interested', false);
});
