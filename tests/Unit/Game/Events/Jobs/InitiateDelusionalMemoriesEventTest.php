<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ModelsEvent;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Jobs\InitiateDelusionalMemoriesEvent;
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

class InitiateDelusionalMemoriesEventTest extends TestCase
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

    public function test_deslusional_memories_does_not_trigger_for_invalid_scheduled_event()
    {
        Event::fake();

        InitiateDelusionalMemoriesEvent::dispatch(rand(10000, 99999));

        Event::assertNotDispatched(GlobalMessageEvent::class);
        $this->assertEmpty(Announcement::all());
        $this->assertEmpty(ModelsEvent::all());
        $this->assertEmpty(GlobalEventGoal::all());
    }

    public function test_delusional_memories_event_does_trigger_scheduled_event_exists()
    {

        $this->createGameMap([
            'name' => MapNameValue::DELUSIONAL_MEMORIES,
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        Event::fake();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        InitiateDelusionalMemoriesEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
        $this->assertNotEmpty(GlobalEventGoal::all());
    }

    public function test_delusional_start_creates_current_child_raids_only(): void
    {
        $this->createGameMap([
            'name' => MapNameValue::DELUSIONAL_MEMORIES,
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $raidMap = $this->createGameMap(['name' => 'DelRaidMap', 'default' => false]);
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
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'start_date' => $now,
            'end_date' => $parentEnd,
            'raids_for_event' => [[
                'selected_raid' => $raid->id,
                'start_date' => $now->copy()->addDays(5)->format('Y-m-d H:i:s'),
                'end_date' => $now->copy()->addDays(10)->format('Y-m-d H:i:s'),
            ]],
        ]);

        Event::fake();

        InitiateDelusionalMemoriesEvent::dispatch($event->id);

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

    public function test_delusional_start_reschedules_only_parent_for_next_year(): void
    {
        $this->createGameMap([
            'name' => MapNameValue::DELUSIONAL_MEMORIES,
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $raidMap = $this->createGameMap(['name' => 'DelRaidMap2', 'default' => false]);
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
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'start_date' => $now,
            'end_date' => $now->copy()->addMonths(2),
            'raids_for_event' => [[
                'selected_raid' => $raid->id,
                'start_date' => $now->copy()->addDays(5)->format('Y-m-d H:i:s'),
                'end_date' => $now->copy()->addDays(10)->format('Y-m-d H:i:s'),
            ]],
        ]);

        Event::fake();

        InitiateDelusionalMemoriesEvent::dispatch($event->id);

        $nextYearParent = ScheduledEvent::where('event_type', EventType::DELUSIONAL_MEMORIES_EVENT)
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
}
