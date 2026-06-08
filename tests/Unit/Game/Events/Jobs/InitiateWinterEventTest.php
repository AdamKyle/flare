<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ModelsEvent;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Jobs\InitiateWinterEvent;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class InitiateWinterEventTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateLocation, CreateMonster, CreateRaid, CreateScheduledEvent, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_winter_event_does_not_trigger()
    {
        Event::fake();

        InitiateWinterEvent::dispatch(rand(1000, 9999));

        Event::assertNotDispatched(GlobalMessageEvent::class);
        $this->assertEmpty(Announcement::all());
        $this->assertEmpty(ModelsEvent::all());
        $this->assertEmpty(GlobalEventGoal::all());
    }

    public function test_winter_event_does_trigger_when_scheduled_event_exists()
    {

        $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'only_during_event_type' => EventType::WINTER_EVENT,
        ]);

        Event::fake();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
        ]);

        InitiateWinterEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
        $this->assertNotEmpty(GlobalEventGoal::all());
    }

    public function test_schedule_the_event_for_next_year()
    {
        $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'only_during_event_type' => EventType::WINTER_EVENT,
        ]);

        Event::fake();

        $now = now();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $now,
            'end_date' => $now,
        ]);

        InitiateWinterEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
        $this->assertNotEmpty(GlobalEventGoal::all());

        $eventForNextYear = ScheduledEvent::where('start_date', $now->clone()->addYear())->where('event_type', EventType::WINTER_EVENT)->first();

        $this->assertNotNull($eventForNextYear);
        $this->assertNull($eventForNextYear->raids_for_event);
    }

    public function test_schedule_the_event_and_associated_raids_for_next_year()
    {

        $monster = $this->createMonster();
        $item = $this->createItem();

        $location = $this->createLocation();

        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_monster_ids' => [$monster->id],
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [$location->id],
            'artifact_item_id' => $item->id,
            'scheduled_event_description' => 'test description',
        ]);

        $now = now();

        $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'only_during_event_type' => EventType::WINTER_EVENT,
        ]);

        Event::fake();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $now,
            'end_date' => $now,
            'raids_for_event' => [[
                'selected_raid' => $raid->id,
                'start_date' => $now,
                'end_date' => $now,
            ]],
        ]);

        InitiateWinterEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
        $this->assertNotEmpty(GlobalEventGoal::all());

        $eventForNextYear = ScheduledEvent::where('start_date', $now->clone()->addYear())->where('event_type', EventType::WINTER_EVENT)->first();

        $this->assertNotNull($eventForNextYear);
        $this->assertNotNull($eventForNextYear->raids_for_event);
    }

    public function test_winter_start_creates_current_child_raids_only(): void
    {
        $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'only_during_event_type' => EventType::WINTER_EVENT,
        ]);

        $raidMap = $this->createGameMap(['name' => 'RaidMap', 'default' => false]);
        $location = $this->createLocation(['game_map_id' => $raidMap->id]);
        $monster = $this->createMonster();
        $item = $this->createItem();
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $location->id,
            'scheduled_event_description' => 'test',
        ]);

        $now = now();
        $parentEnd = $now->copy()->addMonths(2);

        $event = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $now,
            'end_date' => $parentEnd,
            'raids_for_event' => [[
                'selected_raid' => $raid->id,
                'start_date' => $now->copy()->addDays(5)->format('Y-m-d H:i:s'),
                'end_date' => $now->copy()->addDays(10)->format('Y-m-d H:i:s'),
            ]],
        ]);

        Event::fake();

        InitiateWinterEvent::dispatch($event->id);

        $currentChildRaids = ScheduledEvent::where('raid_id', $raid->id)
            ->where('event_type', EventType::RAID_EVENT)
            ->get();

        $this->assertCount(1, $currentChildRaids);

        $nextYearChildRaids = ScheduledEvent::where('raid_id', $raid->id)
            ->where('event_type', EventType::RAID_EVENT)
            ->where('start_date', '>=', $now->copy()->addYear())
            ->get();

        $this->assertCount(0, $nextYearChildRaids);
    }

    public function test_winter_start_reschedules_only_parent_for_next_year(): void
    {
        $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'only_during_event_type' => EventType::WINTER_EVENT,
        ]);

        $raidMap = $this->createGameMap(['name' => 'RaidMap2', 'default' => false]);
        $location = $this->createLocation(['game_map_id' => $raidMap->id]);
        $monster = $this->createMonster();
        $item = $this->createItem();
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $location->id,
            'scheduled_event_description' => 'test',
        ]);

        $now = now();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $now,
            'end_date' => $now->copy()->addMonths(2),
            'raids_for_event' => [[
                'selected_raid' => $raid->id,
                'start_date' => $now->copy()->addDays(5)->format('Y-m-d H:i:s'),
                'end_date' => $now->copy()->addDays(10)->format('Y-m-d H:i:s'),
            ]],
        ]);

        Event::fake();

        InitiateWinterEvent::dispatch($event->id);

        $nextYearParent = ScheduledEvent::where('event_type', EventType::WINTER_EVENT)
            ->where('id', '!=', $event->id)
            ->first();

        $this->assertNotNull($nextYearParent);
        $this->assertNotNull($nextYearParent->raids_for_event);

        $nextYearChildRaids = ScheduledEvent::where('raid_id', $raid->id)
            ->where('event_type', EventType::RAID_EVENT)
            ->where('start_date', '>=', $now->copy()->addYear())
            ->get();

        $this->assertCount(0, $nextYearChildRaids);
    }

    public function test_next_year_child_raids_are_not_created_early(): void
    {
        $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'only_during_event_type' => EventType::WINTER_EVENT,
        ]);

        $raidMap = $this->createGameMap(['name' => 'RaidMap3', 'default' => false]);
        $location = $this->createLocation(['game_map_id' => $raidMap->id]);
        $monster = $this->createMonster();
        $item = $this->createItem();
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $location->id,
            'scheduled_event_description' => 'test',
        ]);

        $now = now();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $now,
            'end_date' => $now->copy()->addMonths(2),
            'raids_for_event' => [[
                'selected_raid' => $raid->id,
                'start_date' => $now->copy()->addDays(5)->format('Y-m-d H:i:s'),
                'end_date' => $now->copy()->addDays(10)->format('Y-m-d H:i:s'),
            ]],
        ]);

        Event::fake();

        InitiateWinterEvent::dispatch($event->id);

        $nextYearChildRaids = ScheduledEvent::where('raid_id', $raid->id)
            ->where('event_type', EventType::RAID_EVENT)
            ->where('start_date', '>=', $now->copy()->addYear())
            ->get();

        $this->assertCount(0, $nextYearChildRaids);
    }
}
