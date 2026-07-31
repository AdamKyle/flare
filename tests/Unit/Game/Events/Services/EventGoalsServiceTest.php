<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGlobalEventGoal;

class EventGoalsServiceTest extends TestCase
{
    use CreateEvent, CreateGameMap, CreateGlobalEventGoal, RefreshDatabase;

    private ?EventGoalsService $eventGoalsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventGoalsService = resolve(EventGoalsService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->eventGoalsService = null;
    }

    public function test_returns_goal_for_character_current_event_map(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $schedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id]);

        $goal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $event->id,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $result = $this->eventGoalsService->getEventGoalData($character);

        $this->assertNotNull($result['event_goals']);
        $this->assertEquals($goal->max_kills, $result['event_goals']['max_kills']);
        $this->assertEquals(ItemSpecialtyType::CORRUPTED_ICE, $result['event_goals']['reward']);
    }

    public function test_reads_current_kills_crafts_enchants_from_rows_matching_active_goal_id(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $schedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id]);

        $goal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $event->id,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $otherSchedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $otherEvent = $this->createEvent(['type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'scheduled_event_id' => $otherSchedule->id]);

        $otherGoal = $this->createGlobalEventGoal([
            'max_kills' => 50,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $otherEvent->id,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $character->globalEventKills()->create([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'kills' => 7,
        ]);

        $character->globalEventKills()->create([
            'global_event_goal_id' => $otherGoal->id,
            'character_id' => $character->id,
            'kills' => 99,
        ]);

        $character = $character->refresh();

        $result = $this->eventGoalsService->getEventGoalData($character);

        $this->assertEquals(7, $result['event_goals']['current_kills']);
    }

    public function test_payload_shows_updated_current_crafts_on_winter_map(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $schedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id]);

        $goal = $this->createGlobalEventGoal([
            'max_crafts' => 50,
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $event->id,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $character->globalEventCrafts()->create([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'crafts' => 5,
        ]);

        $character = $character->refresh();

        $result = $this->eventGoalsService->getEventGoalData($character);

        $this->assertEquals(5, $result['event_goals']['current_crafts']);
    }

    public function test_payload_shows_updated_current_enchants_on_winter_map(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $schedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id]);

        $goal = $this->createGlobalEventGoal([
            'max_enchants' => 50,
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $event->id,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $character->globalEventEnchants()->create([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'enchants' => 3,
        ]);

        $character = $character->refresh();

        $result = $this->eventGoalsService->getEventGoalData($character);

        $this->assertEquals(3, $result['event_goals']['current_enchants']);
    }

    public function test_payload_shows_updated_current_kills_on_winter_map(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $schedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id]);

        $goal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $event->id,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $character->globalEventKills()->create([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'kills' => 15,
        ]);

        $character = $character->refresh();

        $result = $this->eventGoalsService->getEventGoalData($character);

        $this->assertEquals(15, $result['event_goals']['current_kills']);
    }

    public function test_delusional_memories_map_shows_updated_kills_when_battle_step_active(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::DELUSIONAL_MEMORIES,
                'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $schedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $event = $this->createEvent(['type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'scheduled_event_id' => $schedule->id]);

        $goal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $character->globalEventKills()->create([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'kills' => 22,
        ]);

        $character = $character->refresh();

        $result = $this->eventGoalsService->getEventGoalData($character);

        $this->assertNotNull($result['event_goals']);
        $this->assertEquals(22, $result['event_goals']['current_kills']);
    }

    public function test_character_off_event_map_gets_null_event_goals(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->eventGoalsService->getEventGoalData($character);

        $this->assertNull($result['event_goals']);
    }

    public function test_event_goals_are_null_when_schedule_has_not_started_yet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
                'only_during_event_type' => EventType::WINTER_EVENT,
            ])->id,
        ]);

        $character = $character->refresh();

        $schedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::QUEUED,
        ]);

        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id]);

        $this->createGlobalEventGoal([
            'max_kills' => 100,
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $event->id,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
        ]);

        $result = $this->eventGoalsService->getEventGoalData($character);

        $this->assertNull($result['event_goals']);
    }
}
