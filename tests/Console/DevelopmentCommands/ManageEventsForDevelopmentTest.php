<?php

namespace Tests\Console\DevelopmentCommands;

use App\Console\DevelopmentCommands\ManageEventsForDevelopment;
use App\Flare\Models\Event;
use App\Flare\Models\Monster;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Jobs\InitiateDelusionalMemoriesEvent;
use App\Game\Events\Services\DevelopmentEventManager;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Raids\Jobs\InitiateRaid;
use App\Game\Raids\Values\RaidType;
use Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class ManageEventsForDevelopmentTest extends TestCase
{
    use CreateGameMap, CreateLocation, CreateRaid, CreateScheduledEvent, InteractsWithConsole, RefreshDatabase;

    public function test_command_signature_is_registered(): void
    {
        $this->assertArrayHasKey('manage:events-for-development', Artisan::all());
    }

    public function test_old_command_signature_is_not_registered(): void
    {
        $this->assertArrayNotHasKey('create:events-for-development', Artisan::all());
    }

    public function test_view_cancel_menu_label_is_currently_running_events(): void
    {
        $this->assertEquals('View / cancel currently running events', ManageEventsForDevelopment::VIEW_CANCEL);
    }

    public function test_non_local_non_testing_environment_rejects_command_with_no_changes(): void
    {
        config(['app.env' => 'production']);

        $this->artisan('manage:events-for-development')
            ->assertFailed();

        $this->assertEquals(0, ScheduledEvent::count());

        config(['app.env' => 'testing']);
    }

    public function test_missing_queue_default_rejects_before_event_selection(): void
    {
        config(['queue.default' => null]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsOutput('The configured default queue connection does not support the required one-minute delayed start.')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(0, ScheduledEvent::count());
    }

    public function test_missing_queue_connection_rejects_before_event_selection(): void
    {
        config(['queue.default' => 'connection-that-does-not-exist']);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsOutput('The configured default queue connection does not support the required one-minute delayed start.')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(0, ScheduledEvent::count());
    }

    public function test_missing_queue_driver_rejects_before_event_selection(): void
    {
        config(['queue.default' => 'broken']);
        config(['queue.connections.broken' => ['no_driver_key' => true]]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsOutput('The configured default queue connection does not support the required one-minute delayed start.')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(0, ScheduledEvent::count());
    }

    public function test_sync_queue_driver_rejects_start_operation_before_any_schedule_is_created(): void
    {
        config(['queue.default' => 'sync']);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(0, ScheduledEvent::count());
    }

    public function test_null_queue_driver_rejects_start_operation_before_any_schedule_is_created(): void
    {
        config(['queue.default' => 'null']);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(0, ScheduledEvent::count());
    }

    public function test_exit_works(): void
    {
        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();
    }

    public function test_no_events_running_prints_exact_empty_message(): void
    {
        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsOutput('No events or raids are running right now.')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();
    }

    public function test_one_weekly_event_is_queued_with_start_one_minute_ahead_and_five_minute_duration(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::WEEKLY_CELESTIALS])
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $schedule = ScheduledEvent::where('event_type', EventType::WEEKLY_CELESTIALS)->firstOrFail();

        $this->assertEquals(ScheduledEventStatus::QUEUED, $schedule->status);
        $this->assertEquals(300, $schedule->start_date->diffInSeconds($schedule->end_date));
    }

    public function test_multiple_compatible_choices_create_all_schedules_with_identical_timestamps(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [
                DevelopmentEventManager::WEEKLY_CELESTIALS,
                DevelopmentEventManager::WEEKLY_CURRENCY_DROPS,
            ])
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $celestials = ScheduledEvent::where('event_type', EventType::WEEKLY_CELESTIALS)->firstOrFail();
        $currencyDrops = ScheduledEvent::where('event_type', EventType::WEEKLY_CURRENCY_DROPS)->firstOrFail();

        $this->assertTrue($celestials->start_date->equalTo($currencyDrops->start_date));
        $this->assertTrue($celestials->end_date->equalTo($currencyDrops->end_date));
    }

    public function test_independent_raid_prompts_and_queues_selected_raid(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $raid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $schedule = ScheduledEvent::where('raid_id', $raid->id)->firstOrFail();

        $this->assertEquals(ScheduledEventStatus::QUEUED, $schedule->status);
        $this->assertNull($schedule->parent_scheduled_event_id);
    }

    public function test_winter_creates_parent_and_exact_child(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::WINTER_EVENT])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::WINTER_EVENT.'?', $raid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $parent = ScheduledEvent::where('event_type', EventType::WINTER_EVENT)->firstOrFail();
        $child = ScheduledEvent::where('raid_id', $raid->id)->firstOrFail();

        $this->assertEquals($parent->id, $child->parent_scheduled_event_id);
        $this->assertEquals(ScheduledEventStatus::QUEUED, $parent->status);
        $this->assertEquals(ScheduledEventStatus::SCHEDULED, $child->status);
    }

    public function test_delusional_creates_parent_and_exact_child(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::DELUSIONAL_EVENT])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::DELUSIONAL_EVENT.'?', $raid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $parent = ScheduledEvent::where('event_type', EventType::DELUSIONAL_MEMORIES_EVENT)->firstOrFail();
        $child = ScheduledEvent::where('raid_id', $raid->id)->firstOrFail();

        $this->assertEquals($parent->id, $child->parent_scheduled_event_id);
        $this->assertEquals(ScheduledEventStatus::QUEUED, $parent->status);
        $this->assertEquals(ScheduledEventStatus::SCHEDULED, $child->status);
    }

    public function test_no_raids_aborts_whole_start_operation(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(0, ScheduledEvent::count());
    }

    public function test_duplicate_active_weekly_type_is_rejected(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::WEEKLY_CELESTIALS])
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(1, ScheduledEvent::count());
    }

    public function test_duplicate_active_winter_is_rejected(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::WINTER_EVENT])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::WINTER_EVENT.'?', $raid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(1, ScheduledEvent::where('event_type', EventType::WINTER_EVENT)->count());
        $this->assertEquals(0, ScheduledEvent::where('raid_id', $raid->id)->count());
    }

    public function test_duplicate_active_delusional_is_rejected(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::DELUSIONAL_EVENT])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::DELUSIONAL_EVENT.'?', $raid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(1, ScheduledEvent::where('event_type', EventType::DELUSIONAL_MEMORIES_EVENT)->count());
        $this->assertEquals(0, ScheduledEvent::where('raid_id', $raid->id)->count());
    }

    public function test_active_winter_permits_new_delusional_start(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::DELUSIONAL_EVENT])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::DELUSIONAL_EVENT.'?', $raid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(1, ScheduledEvent::where('event_type', EventType::DELUSIONAL_MEMORIES_EVENT)->count());
        $this->assertEquals(1, ScheduledEvent::where('raid_id', $raid->id)->count());
    }

    public function test_active_delusional_permits_new_winter_start(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::WINTER_EVENT])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::WINTER_EVENT.'?', $raid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(1, ScheduledEvent::where('event_type', EventType::WINTER_EVENT)->count());
        $this->assertEquals(1, ScheduledEvent::where('raid_id', $raid->id)->count());
    }

    public function test_winter_and_delusional_can_start_together(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMapOne = $this->createGameMap(['name' => 'Ice Plane']);
        $gameMapTwo = $this->createGameMap(['name' => 'Delusional Memories']);
        $locationOne = $this->createLocation(['game_map_id' => $gameMapOne->id]);
        $locationTwo = $this->createLocation(['game_map_id' => $gameMapTwo->id]);

        $winterRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $locationOne->id,
            'corrupted_location_ids' => [],
        ]);

        $delusionalRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $locationTwo->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [
                DevelopmentEventManager::WINTER_EVENT,
                DevelopmentEventManager::DELUSIONAL_EVENT,
            ])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::WINTER_EVENT.'?', $winterRaid->name)
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::DELUSIONAL_EVENT.'?', $delusionalRaid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(1, ScheduledEvent::where('event_type', EventType::WINTER_EVENT)->count());
        $this->assertEquals(1, ScheduledEvent::where('event_type', EventType::DELUSIONAL_MEMORIES_EVENT)->count());
    }

    public function test_same_map_requested_raids_reject_before_creation(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);

        $raidOne = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $raidTwo = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [
                DevelopmentEventManager::WINTER_EVENT,
                DevelopmentEventManager::DELUSIONAL_EVENT,
            ])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::WINTER_EVENT.'?', $raidOne->name)
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::DELUSIONAL_EVENT.'?', $raidTwo->name)
            ->expectsOutput('Requested raids '.$raidOne->name.' and '.$raidTwo->name.' use the same map(s): '.$gameMap->name.'.')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(0, ScheduledEvent::count());
    }

    public function test_existing_same_map_conflict_displays_exact_map_names(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);

        $activeRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $activeRaid->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $requestedRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $requestedRaid->name)
            ->expectsOutput('Raid '.$activeRaid->name.' is active on the same map(s) as '.$requestedRaid->name.': '.$gameMap->name.'.')
            ->expectsConfirmation('Cancel the current raid and continue?', 'no')
            ->expectsOutput('No changes made.')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(1, ScheduledEvent::count());
        $this->assertEquals(0, ScheduledEvent::where('raid_id', $requestedRaid->id)->count());
    }

    public function test_confirming_conflict_tears_down_old_raid_before_creating_replacement(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);

        $activeRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $activeSchedule = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $activeRaid->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $requestedRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $requestedRaid->name)
            ->expectsConfirmation('Cancel the current raid and continue?', 'yes')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $activeSchedule->fresh()->status);
        $this->assertEquals(1, ScheduledEvent::where('raid_id', $requestedRaid->id)->count());
    }

    public function test_running_rows_display(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $scheduledEvent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->doesntExpectOutput('No events or raids are running right now.')
            ->expectsQuestion('Select item(s) to cancel, or back:', [ManageEventsForDevelopment::BACK])
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();
    }

    public function test_terminal_rows_do_not_display(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::COMPLETED,
            'currently_running' => false,
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsOutput('No events or raids are running right now.')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();
    }

    public function test_future_scheduled_event_is_not_displayed(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsOutput('No events or raids are running right now.')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();
    }

    public function test_queued_event_is_not_displayed(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::QUEUED,
            'currently_running' => false,
            'start_date' => now()->addMinute(),
            'end_date' => now()->addMinutes(6),
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsOutput('No events or raids are running right now.')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();
    }

    public function test_starting_event_is_not_displayed(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::STARTING,
            'currently_running' => true,
            'start_date' => now()->subMinute(),
            'end_date' => now()->addMinutes(5),
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsOutput('No events or raids are running right now.')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();
    }

    public function test_cancelling_event_is_not_displayed(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::CANCELLING,
            'currently_running' => true,
            'start_date' => now()->subMinute(),
            'end_date' => now()->addMinutes(5),
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsOutput('No events or raids are running right now.')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();
    }

    public function test_expired_stale_running_event_is_not_displayed(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(10),
            'end_date' => now()->subMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $scheduledEvent->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(5),
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsOutput('No events or raids are running right now.')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();
    }

    public function test_future_child_raid_under_running_seasonal_parent_is_not_displayed(): void
    {
        $parent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $parent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsChoice(
                'Select item(s) to cancel, or back:',
                [ManageEventsForDevelopment::BACK],
                [
                    ManageEventsForDevelopment::CANCEL_ALL,
                    '#'.$parent->id.' '.$parent->getTitleOfEvent().' ['.ScheduledEventStatus::RUNNING.']',
                    ManageEventsForDevelopment::BACK,
                ]
            )
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();
    }

    public function test_cancellation_choices_include_only_rows_running_now(): void
    {
        $runningSchedule = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $runningSchedule->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsChoice(
                'Select item(s) to cancel, or back:',
                [ManageEventsForDevelopment::BACK],
                [
                    ManageEventsForDevelopment::CANCEL_ALL,
                    '#'.$runningSchedule->id.' '.$runningSchedule->getTitleOfEvent().' ['.ScheduledEventStatus::RUNNING.']',
                    ManageEventsForDevelopment::BACK,
                ]
            )
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();
    }

    public function test_direct_child_cancellation_leaves_parent_active(): void
    {
        $parent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $parent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $child = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::RAID_EVENT,
            'scheduled_event_id' => $child->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsQuestion('Select item(s) to cancel, or back:', ['#'.$child->id.' '.$raid->name.' ['.ScheduledEventStatus::RUNNING.']'])
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $child->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::RUNNING, $parent->fresh()->status);
    }

    public function test_parent_cancellation_lists_only_currently_running_children(): void
    {
        $parent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $parent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $historicalRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);
        $futureRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);
        $activeRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $historicalRaid->id,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::CANCELLED,
            'currently_running' => false,
            'start_date' => now()->subMinutes(20),
            'end_date' => now()->subMinutes(15),
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $futureRaid->id,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        $activeChild = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $activeRaid->id,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::RAID_EVENT,
            'scheduled_event_id' => $activeChild->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsQuestion('Select item(s) to cancel, or back:', ['#'.$parent->id.' '.$parent->getTitleOfEvent().' ['.ScheduledEventStatus::RUNNING.']'])
            ->expectsOutput($parent->getTitleOfEvent().' has currently running child raid(s) which will be cancelled first: '.$activeRaid->name)
            ->expectsConfirmation('Cancel '.$parent->getTitleOfEvent().' and its currently running child raids?', 'no')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(ScheduledEventStatus::RUNNING, $parent->fresh()->status);
    }

    public function test_confirming_parent_cancellation_ends_children_first(): void
    {
        $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);
        $this->createGameMap(['name' => MapNameValue::SURFACE]);

        $parent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $parent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $child = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::RAID_EVENT,
            'scheduled_event_id' => $child->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsQuestion('Select item(s) to cancel, or back:', ['#'.$parent->id.' '.$parent->getTitleOfEvent().' ['.ScheduledEventStatus::RUNNING.']'])
            ->expectsConfirmation('Cancel '.$parent->getTitleOfEvent().' and its currently running child raids?', 'yes')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $parent->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::CANCELLED, $child->fresh()->status);
    }

    public function test_cancel_all_currently_running_reaches_terminal_cancelled_state(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $scheduledEvent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsQuestion('Select item(s) to cancel, or back:', [ManageEventsForDevelopment::CANCEL_ALL])
            ->expectsOutput('All currently running events and raids have been cancelled.')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $scheduledEvent->fresh()->status);
    }

    public function test_cancel_all_currently_running_ignores_scheduled_future_events(): void
    {
        $runningSchedule = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $runningSchedule->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $futureSchedule = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsQuestion('Select item(s) to cancel, or back:', [ManageEventsForDevelopment::CANCEL_ALL])
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $runningSchedule->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::SCHEDULED, $futureSchedule->fresh()->status);
    }

    public function test_back_returns_to_main(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $scheduledEvent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::VIEW_CANCEL)
            ->expectsQuestion('Select item(s) to cancel, or back:', [ManageEventsForDevelopment::BACK])
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(ScheduledEventStatus::RUNNING, $scheduledEvent->fresh()->status);
    }

    public function test_non_seasonal_independent_raid_does_not_prompt_for_seasonal_event(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $raid->name)
            ->doesntExpectOutput($raid->name.' requires '.DevelopmentEventManager::WINTER_EVENT.' to be running.')
            ->doesntExpectOutput($raid->name.' requires '.DevelopmentEventManager::DELUSIONAL_EVENT.' to be running.')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $schedule = ScheduledEvent::where('raid_id', $raid->id)->firstOrFail();

        $this->assertNull($schedule->parent_scheduled_event_id);
    }

    public function test_independent_raid_becomes_child_of_exact_running_winter_schedule(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $winterParent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $winterParent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'raid_type' => RaidType::ICE_QUEEN,
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $raid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $child = ScheduledEvent::where('raid_id', $raid->id)->firstOrFail();

        $this->assertEquals($winterParent->id, $child->parent_scheduled_event_id);
    }

    public function test_independent_raid_becomes_child_of_exact_running_delusional_schedule(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $delusionalParent = $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $delusionalParent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'raid_type' => RaidType::JESTER_OF_TIME,
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $raid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $child = ScheduledEvent::where('raid_id', $raid->id)->firstOrFail();

        $this->assertEquals($delusionalParent->id, $child->parent_scheduled_event_id);
    }

    public function test_winter_raid_without_winter_running_displays_requirement_message(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);

        $seasonalRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'raid_type' => RaidType::ICE_QUEEN,
        ]);

        $plainRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $seasonalRaid->name)
            ->expectsOutput($seasonalRaid->name.' requires '.DevelopmentEventManager::WINTER_EVENT.' to be running.')
            ->expectsConfirmation('Would you like to start '.DevelopmentEventManager::WINTER_EVENT.' as well?', 'no')
            ->expectsOutput('You must have '.DevelopmentEventManager::WINTER_EVENT.' running to start '.$seasonalRaid->name.'.')
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $plainRaid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();
    }

    public function test_declining_seasonal_start_displays_mandatory_message(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);

        $seasonalRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'raid_type' => RaidType::JESTER_OF_TIME,
        ]);

        $plainRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $seasonalRaid->name)
            ->expectsConfirmation('Would you like to start '.DevelopmentEventManager::DELUSIONAL_EVENT.' as well?', 'no')
            ->expectsOutput('You must have '.DevelopmentEventManager::DELUSIONAL_EVENT.' running to start '.$seasonalRaid->name.'.')
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $plainRaid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();
    }

    public function test_declining_seasonal_start_returns_to_raid_selection_for_a_different_raid(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);

        $seasonalRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'raid_type' => RaidType::ICE_QUEEN,
        ]);

        $plainRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $seasonalRaid->name)
            ->expectsConfirmation('Would you like to start '.DevelopmentEventManager::WINTER_EVENT.' as well?', 'no')
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $plainRaid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(1, ScheduledEvent::where('raid_id', $plainRaid->id)->count());
        $this->assertEquals(0, ScheduledEvent::where('raid_id', $seasonalRaid->id)->count());
    }

    public function test_declining_seasonal_start_creates_no_schedules(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);

        $seasonalRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'raid_type' => RaidType::ICE_QUEEN,
        ]);

        $plainRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $seasonalRaid->name)
            ->expectsConfirmation('Would you like to start '.DevelopmentEventManager::WINTER_EVENT.' as well?', 'no')
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $plainRaid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(0, ScheduledEvent::where('raid_id', $seasonalRaid->id)->count());
        $this->assertEquals(0, ScheduledEvent::where('event_type', EventType::WINTER_EVENT)->count());
    }

    public function test_accepting_seasonal_start_creates_winter_parent_and_exact_child_raid(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'raid_type' => RaidType::ICE_QUEEN,
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $raid->name)
            ->expectsConfirmation('Would you like to start '.DevelopmentEventManager::WINTER_EVENT.' as well?', 'yes')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $parent = ScheduledEvent::where('event_type', EventType::WINTER_EVENT)->firstOrFail();
        $child = ScheduledEvent::where('raid_id', $raid->id)->firstOrFail();

        $this->assertEquals($parent->id, $child->parent_scheduled_event_id);
        $this->assertEquals(ScheduledEventStatus::QUEUED, $parent->status);
        $this->assertEquals(ScheduledEventStatus::SCHEDULED, $child->status);
    }

    public function test_accepting_seasonal_start_creates_delusional_parent_and_exact_child_raid(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'raid_type' => RaidType::CORRUPTED_BISHOP,
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $raid->name)
            ->expectsConfirmation('Would you like to start '.DevelopmentEventManager::DELUSIONAL_EVENT.' as well?', 'yes')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $parent = ScheduledEvent::where('event_type', EventType::DELUSIONAL_MEMORIES_EVENT)->firstOrFail();
        $child = ScheduledEvent::where('raid_id', $raid->id)->firstOrFail();

        $this->assertEquals($parent->id, $child->parent_scheduled_event_id);
        $this->assertEquals(ScheduledEventStatus::QUEUED, $parent->status);
        $this->assertEquals(ScheduledEventStatus::SCHEDULED, $child->status);
    }

    public function test_accepting_seasonal_start_parent_and_child_share_exact_timestamps(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'raid_type' => RaidType::ICE_QUEEN,
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $raid->name)
            ->expectsConfirmation('Would you like to start '.DevelopmentEventManager::WINTER_EVENT.' as well?', 'yes')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $parent = ScheduledEvent::where('event_type', EventType::WINTER_EVENT)->firstOrFail();
        $child = ScheduledEvent::where('raid_id', $raid->id)->firstOrFail();

        $this->assertTrue($parent->start_date->equalTo($child->start_date));
        $this->assertTrue($parent->end_date->equalTo($child->end_date));
    }

    public function test_accepting_seasonal_start_dispatches_only_the_seasonal_root_job(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'raid_type' => RaidType::JESTER_OF_TIME,
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $raid->name)
            ->expectsConfirmation('Would you like to start '.DevelopmentEventManager::DELUSIONAL_EVENT.' as well?', 'yes')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        Queue::assertPushed(InitiateDelusionalMemoriesEvent::class, 1);
        Queue::assertNotPushed(InitiateRaid::class);
    }

    public function test_accepting_seasonal_start_does_not_prompt_for_a_second_raid(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'raid_type' => RaidType::ICE_QUEEN,
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $raid->name)
            ->expectsConfirmation('Would you like to start '.DevelopmentEventManager::WINTER_EVENT.' as well?', 'yes')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $this->assertEquals(1, ScheduledEvent::where('raid_id', $raid->id)->count());
    }

    public function test_seasonal_raid_attached_to_running_parent_is_never_created_as_root_schedule(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $winterParent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $winterParent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'raid_type' => RaidType::ICE_QUEEN,
        ]);

        $this->artisan('manage:events-for-development')
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::START)
            ->expectsQuestion('Which event(s) would you like to start?', [DevelopmentEventManager::INDEPENDENT_RAID])
            ->expectsQuestion('Which raid would you like to use for: '.DevelopmentEventManager::INDEPENDENT_RAID.'?', $raid->name)
            ->expectsQuestion('What would you like to do?', ManageEventsForDevelopment::EXIT)
            ->assertSuccessful();

        $child = ScheduledEvent::where('raid_id', $raid->id)->firstOrFail();

        $this->assertNotNull($child->parent_scheduled_event_id);
        $this->assertEquals($winterParent->id, $child->parent_scheduled_event_id);
    }
}
