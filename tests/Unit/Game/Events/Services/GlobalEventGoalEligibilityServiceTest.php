<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Events\Values\ScheduledEventStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGlobalEventGoal;

class GlobalEventGoalEligibilityServiceTest extends TestCase
{
    use CreateEvent, CreateGameMap, CreateGlobalEventGoal, RefreshDatabase;

    public function test_crafting_eligibility_returns_exact_map_event_goal(): void
    {
        $schedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $result = resolve(GlobalEventGoalEligibilityService::class)->currentCraftingGoalFor($character);

        $this->assertNotNull($result);
        $this->assertEquals($goal->id, $result->id);
    }

    public function test_enchanting_eligibility_returns_exact_map_event_goal(): void
    {
        $schedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $event = $this->createEvent([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
            'ends_at' => now()->addHour(),
        ]);

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $event->id,
            'max_enchants' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::WINTER_EVENT]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $result = resolve(GlobalEventGoalEligibilityService::class)->currentEnchantingGoalFor($character);

        $this->assertNotNull($result);
        $this->assertEquals($goal->id, $result->id);
    }

    public function test_crafting_goal_is_null_when_character_not_on_event_map(): void
    {
        $schedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = resolve(GlobalEventGoalEligibilityService::class)->currentCraftingGoalFor($character);

        $this->assertNull($result);
    }

    public function test_crafting_goal_is_null_when_schedule_has_not_started_yet(): void
    {
        $schedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::QUEUED,
        ]);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $result = resolve(GlobalEventGoalEligibilityService::class)->currentCraftingGoalFor($character);

        $this->assertNull($result);
    }

    public function test_event_for_character_map_resolves_winter_and_delusional_independently(): void
    {
        $winterSchedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $delusionalSchedule = ScheduledEvent::factory()->create([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $winterEvent = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $winterSchedule->id, 'ends_at' => now()->addHour()]);
        $delusionalEvent = $this->createEvent(['type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'scheduled_event_id' => $delusionalSchedule->id, 'ends_at' => now()->addHour()]);

        $winterMap = $this->createGameMap(['name' => 'Ice Plane Test', 'only_during_event_type' => EventType::WINTER_EVENT]);
        $delusionalMap = $this->createGameMap(['name' => 'Delusional Test', 'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $characterOnWinterMap = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $winterMap)->getCharacter();
        $characterOnDelusionalMap = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $delusionalMap)->getCharacter();

        $service = resolve(GlobalEventGoalEligibilityService::class);

        $this->assertEquals($winterEvent->id, $service->eventForCharacterMap($characterOnWinterMap)->id);
        $this->assertEquals($delusionalEvent->id, $service->eventForCharacterMap($characterOnDelusionalMap)->id);
    }
}
