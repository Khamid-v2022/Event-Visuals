<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('returns paginated visual grid data with transformed events', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            'display_name' => 'New York, United States',
        ], 200),
    ]);

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
        ->assertJsonPath('data.0.address', 'New York, United States')
        ->assertJsonPath('data.0.images.0', '/imgs/events/event1-1.png')
        ->assertJsonCount(3, 'data.0.images');
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
