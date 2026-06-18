<?php

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventInterest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns calendar data for a month with schedule-based filtering', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'type' => 'concert',
        'status' => 'published',
        'created_time' => 1_700_000_000,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'payload' => [
            'name' => 'June Concert Night',
            'description' => 'A summer concert.',
            'venue' => ['name' => 'The Grand Rooftop'],
            'location' => ['lat' => '40.7128', 'lng' => '-74.0060'],
            'schedule' => ['starts_at' => '1717200000', 'ends_at' => '1717207200'],
            'pricing' => ['currency' => 'USD', 'min_price' => '50.00'],
            'tags' => ['live'],
        ],
    ]);

    $this->getJson(route('events.visual2.data', ['month' => '2024-06']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.name', 'June Concert Night')
        ->assertJsonCount(3, 'data.0.images');
});

it('excludes events outside the calendar grid month range', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => [
            'name' => 'Far Future Event',
            'schedule' => ['starts_at' => '2000000000', 'ends_at' => '2000007200'],
        ],
    ]);

    $this->getJson(route('events.visual2.data', ['month' => '2024-06']))
        ->assertOk()
        ->assertJsonPath('total', 0);
});

it('filters calendar data by search term', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => [
            'name' => 'Unique Jazz Night',
            'description' => '',
            'schedule' => ['starts_at' => '1717200000', 'ends_at' => '1717207200'],
        ],
    ]);
    Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => [
            'name' => 'Other Show',
            'description' => '',
            'schedule' => ['starts_at' => '1717286400', 'ends_at' => '1717293600'],
        ],
    ]);

    $this->getJson(route('events.visual2.data', ['month' => '2024-06', 'q' => 'Jazz']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.name', 'Unique Jazz Night');
});

it('sorts calendar data chronologically by default', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => [
            'name' => 'Later Show',
            'schedule' => ['starts_at' => '1717286400', 'ends_at' => '1717293600'],
        ],
    ]);
    Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => [
            'name' => 'Earlier Show',
            'schedule' => ['starts_at' => '1717200000', 'ends_at' => '1717207200'],
        ],
    ]);

    $this->getJson(route('events.visual2.data', ['month' => '2024-06', 'sort' => 'recent']))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Earlier Show');
});

it('returns interested and booked flags for authenticated users', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => [
            'name' => 'Flagged Event',
            'schedule' => ['starts_at' => '1717200000', 'ends_at' => '1717207200'],
        ],
    ]);

    EventInterest::create(['user_id' => $user->id, 'event_id' => $event->id]);
    EventAttendance::create(['user_id' => $user->id, 'event_id' => $event->id]);

    $this->actingAs($user)
        ->getJson(route('events.visual2.data', ['month' => '2024-06']))
        ->assertOk()
        ->assertJsonPath('data.0.interested', true)
        ->assertJsonPath('data.0.booked', true);
});

it('filters calendar data to interested events only', function () {
    $user = User::factory()->create();
    $interested = Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => [
            'name' => 'Interested Only',
            'schedule' => ['starts_at' => '1717200000', 'ends_at' => '1717207200'],
        ],
    ]);
    Event::factory()->for($user)->create([
        'status' => 'published',
        'payload' => [
            'name' => 'Not Interested',
            'schedule' => ['starts_at' => '1717286400', 'ends_at' => '1717293600'],
        ],
    ]);

    EventInterest::create(['user_id' => $user->id, 'event_id' => $interested->id]);

    $this->actingAs($user)
        ->getJson(route('events.visual2.data', ['month' => '2024-06', 'interested_only' => 1]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.name', 'Interested Only');
});

it('serves cached calendar data without error on repeated requests', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->count(3)->create([
        'status' => 'published',
        'payload' => [
            'name' => 'Cached Calendar Event',
            'schedule' => ['starts_at' => '1717200000', 'ends_at' => '1717207200'],
        ],
    ]);

    $url = route('events.visual2.data', ['month' => '2024-06']);

    $this->getJson($url)->assertOk()->assertJsonCount(3, 'data');
    $this->getJson($url)->assertOk()->assertJsonCount(3, 'data');
});
