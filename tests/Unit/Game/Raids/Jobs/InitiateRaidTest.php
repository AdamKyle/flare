<?php

namespace Tests\Unit\Game\Raids\Jobs;

use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Raids\Jobs\InitiateRaid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class InitiateRaidTest extends TestCase
{
    use CreateGameMap, CreateLocation, CreateMonster, CreateRaid, CreateScheduledEvent, RefreshDatabase;

    public function test_queued_raid_runs_the_complete_story_before_initialization(): void
    {
        Event::fake([GlobalMessageEvent::class]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => $this->createMonster()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'status' => ScheduledEventStatus::QUEUED,
        ]);

        InitiateRaid::dispatch($scheduledEvent->id, ['First sentence.', 'Second sentence.']);

        $this->assertEquals(ScheduledEventStatus::RUNNING, $scheduledEvent->fresh()->status);
        Event::assertDispatched(GlobalMessageEvent::class, fn ($event) => $event->message === 'First sentence.');
        Event::assertDispatched(GlobalMessageEvent::class, fn ($event) => $event->message === 'Second sentence.');
    }

    public function test_cancellation_during_story_prevents_further_initialization(): void
    {
        Event::fake([GlobalMessageEvent::class]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => $this->createMonster()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'status' => ScheduledEventStatus::CANCELLING,
            'currently_running' => true,
        ]);

        InitiateRaid::dispatch($scheduledEvent->id, ['First sentence.']);

        Event::assertNotDispatched(GlobalMessageEvent::class);
        $this->assertEquals(ScheduledEventStatus::CANCELLING, $scheduledEvent->fresh()->status);
    }

    public function test_final_initialization_creates_exact_runtime_event_and_sets_running(): void
    {
        Event::fake([GlobalMessageEvent::class]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => $this->createMonster()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'status' => ScheduledEventStatus::STARTING,
            'currently_running' => true,
        ]);

        InitiateRaid::dispatch($scheduledEvent->id, []);

        $scheduledEvent = $scheduledEvent->fresh();

        $this->assertEquals(ScheduledEventStatus::RUNNING, $scheduledEvent->status);
        $this->assertEquals(1, \App\Flare\Models\Event::where('scheduled_event_id', $scheduledEvent->id)->where('raid_id', $raid->id)->count());
    }

    public function test_duplicate_final_invocation_does_not_duplicate_runtime_event(): void
    {
        Event::fake([GlobalMessageEvent::class]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => $this->createMonster()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'status' => ScheduledEventStatus::STARTING,
            'currently_running' => true,
        ]);

        \App\Flare\Models\Event::create([
            'type' => EventType::RAID_EVENT,
            'started_at' => now(),
            'ends_at' => $scheduledEvent->end_date,
            'raid_id' => $raid->id,
            'scheduled_event_id' => $scheduledEvent->id,
        ]);

        InitiateRaid::dispatch($scheduledEvent->id, []);

        $this->assertEquals(1, \App\Flare\Models\Event::where('scheduled_event_id', $scheduledEvent->id)->count());
    }
}
