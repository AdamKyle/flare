<?php

use App\Game\Events\Values\EventType;
use App\Game\Raids\Values\RaidType;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateScheduledEvent;
use Tests\Traits\CreateUser;

uses(CreateLocation::class, CreateMonster::class, CreateRaid::class, CreateRole::class, CreateScheduledEvent::class, CreateUser::class);

test('guest sees the welcome page', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertViewIs('welcome');
});

test('authenticated admin is redirected to the admin home page', function () {
    $user = $this->createUser();
    $this->createAdminRole();
    $user->assignRole('Admin');

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('home'));
});

test('authenticated non admin is redirected to the game', function () {
    $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

    $response = $this->actingAs($character->user)->get('/');

    $response->assertRedirect(route('game'));
});

test('event calendar page renders', function () {
    $response = $this->get(route('event.calendar'));

    $response->assertOk();
    $response->assertViewIs('event-calendar');
});

test('event page requires an event type', function () {
    $response = $this->get(route('event.type'));

    $response->assertSessionHasErrors('event_type');
});

test('event page redirects to welcome for an unsupported event type', function () {
    $response = $this->get(route('event.type', ['event_type' => 'not-a-real-event']));

    $response->assertRedirect(route('welcome'));
});

test('jester of time raid event page shows the currently running raid', function () {
    $monster = $this->createMonster();
    $location = $this->createLocation(['game_map_id' => $monster->game_map_id]);
    $raid = $this->createRaid([
        'raid_boss_id' => $monster->id,
        'raid_boss_location_id' => $location->id,
        'raid_type' => RaidType::JESTER_OF_TIME,
    ]);
    $scheduledEvent = $this->createScheduledEvent([
        'event_type' => EventType::RAID_EVENT,
        'raid_id' => $raid->id,
        'currently_running' => true,
    ]);

    $response = $this->get(route('event.type', ['event_type' => 'jester-of-time-raid']));

    $response->assertOk();
    $response->assertViewIs('events.jester-of-time-raid.event-page');
    $response->assertViewHas('event', fn ($event) => $event->id === $scheduledEvent->id);
});

test('jester of time raid event page falls back to the next upcoming raid when none is running', function () {
    $monster = $this->createMonster();
    $location = $this->createLocation(['game_map_id' => $monster->game_map_id]);
    $raid = $this->createRaid([
        'raid_boss_id' => $monster->id,
        'raid_boss_location_id' => $location->id,
        'raid_type' => RaidType::JESTER_OF_TIME,
    ]);
    $scheduledEvent = $this->createScheduledEvent([
        'event_type' => EventType::RAID_EVENT,
        'raid_id' => $raid->id,
        'currently_running' => false,
        'start_date' => now()->addDay(),
    ]);

    $response = $this->get(route('event.type', ['event_type' => 'jester-of-time-raid']));

    $response->assertOk();
    $response->assertViewIs('events.jester-of-time-raid.event-page');
    $response->assertViewHas('event', fn ($event) => $event->id === $scheduledEvent->id);
});

test('delusional memories event page shows the currently running event', function () {
    $scheduledEvent = $this->createScheduledEvent([
        'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        'currently_running' => true,
    ]);

    $response = $this->get(route('event.type', ['event_type' => 'delusional-memories']));

    $response->assertOk();
    $response->assertViewIs('events.delusional-memories-event.event-page');
    $response->assertViewHas('event', fn ($event) => $event->id === $scheduledEvent->id);
});

test('delusional memories event page falls back to the next upcoming event when none is running', function () {
    $scheduledEvent = $this->createScheduledEvent([
        'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        'currently_running' => false,
        'start_date' => now()->addDay(),
    ]);

    $response = $this->get(route('event.type', ['event_type' => 'delusional-memories']));

    $response->assertOk();
    $response->assertViewIs('events.delusional-memories-event.event-page');
    $response->assertViewHas('event', fn ($event) => $event->id === $scheduledEvent->id);
});

test('the smugglers are back raid event page renders its view', function () {
    $response = $this->get(route('event.type', ['event_type' => 'the-smugglers-are-back-raid']));

    $response->assertOk();
    $response->assertViewIs('events.the-smugglers-are-back-raid.event-page');
});

test('ice queen raid event page renders its view', function () {
    $response = $this->get(route('event.type', ['event_type' => 'ice-queen-raid']));

    $response->assertOk();
    $response->assertViewIs('events.ice-queen-raid.event-page');
});

test('frozen king raid event page renders its view', function () {
    $response = $this->get(route('event.type', ['event_type' => 'the-frozen-king-raid']));

    $response->assertOk();
    $response->assertViewIs('events.frozen-king-raid.event-page');
});

test('corrupted bishop raid event page renders its view', function () {
    $response = $this->get(route('event.type', ['event_type' => 'corrupted-bishop-raid']));

    $response->assertOk();
    $response->assertViewIs('events.corrupted-bishop-raid.event-page');
});

test('labyrinth monster raid event page renders its view', function () {
    $response = $this->get(route('event.type', ['event_type' => 'labyrinth-monster-raid']));

    $response->assertOk();
    $response->assertViewIs('events.labyrinth-monster-raid.event-page');
});

test('weekly celestials event page renders its view', function () {
    $this->createScheduledEvent([
        'event_type' => EventType::WEEKLY_CELESTIALS,
        'currently_running' => true,
    ]);

    $response = $this->get(route('event.type', ['event_type' => 'weekly-celestials']));

    $response->assertOk();
    $response->assertViewIs('events.weekly-celestials-event.event-page');
});

test('weekly currency drops event page renders its view', function () {
    $this->createScheduledEvent([
        'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
        'currently_running' => true,
    ]);

    $response = $this->get(route('event.type', ['event_type' => 'weekly-currency-drops']));

    $response->assertOk();
    $response->assertViewIs('events.weekly-currency-drops-event.event-page');
});

test('weekly faction loyalty event page renders its view', function () {
    $this->createScheduledEvent([
        'event_type' => EventType::WEEKLY_FACTION_LOYALTY_EVENT,
        'currently_running' => true,
    ]);

    $response = $this->get(route('event.type', ['event_type' => 'weekly-faction-loyalty']));

    $response->assertOk();
    $response->assertViewIs('events.weekly-faction-loyalty-event.event-page');
});

test('the winter event page renders its view', function () {
    $this->createScheduledEvent([
        'event_type' => EventType::WINTER_EVENT,
        'currently_running' => true,
    ]);

    $response = $this->get(route('event.type', ['event_type' => 'the-winter-event']));

    $response->assertOk();
    $response->assertViewIs('events.the-winter-event.event-page');
});
