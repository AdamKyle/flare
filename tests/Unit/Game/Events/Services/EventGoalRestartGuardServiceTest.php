<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Services\EventGoalRestartGuardService;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGlobalEventGoal;

class EventGoalRestartGuardServiceTest extends TestCase
{
    use CreateGlobalEventGoal, RefreshDatabase;

    private ?EventGoalRestartGuardService $service = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(EventGoalRestartGuardService::class);
    }

    protected function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function test_returns_true_when_no_thresholds_configured(): void
    {
        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
            'max_kills' => null,
            'max_crafts' => null,
            'max_enchants' => null,
        ]);

        $this->assertTrue($this->service->shouldRestart($goal->fresh()));
    }

    public function test_returns_false_when_kills_not_met(): void
    {
        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
            'max_kills' => 1000,
            'max_crafts' => null,
            'max_enchants' => null,
        ]);

        // No participation row => totals remain 0
        $this->assertFalse($this->service->shouldRestart($goal->fresh()));
    }

    public function test_returns_true_when_kills_met(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
            'max_kills' => 1000,
            'max_crafts' => null,
            'max_enchants' => null,
        ]);

        // History (optional for guard, but mirrors your console tests)
        $this->createGlobalEventKill([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'kills' => 1000,
        ]);

        // REQUIRED: participation provides the derived total_kills
        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'current_kills' => 1000,
        ]);

        $this->assertTrue($this->service->shouldRestart($goal->fresh()));
    }

    public function test_returns_false_when_crafts_not_met(): void
    {
        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
            'max_kills' => null,
            'max_crafts' => 10,
            'max_enchants' => null,
        ]);

        // No participation row => totals remain 0
        $this->assertFalse($this->service->shouldRestart($goal->fresh()));
    }

    public function test_returns_true_when_crafts_met(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
            'max_kills' => null,
            'max_crafts' => 10,
            'max_enchants' => null,
        ]);

        // History (optional)
        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'crafts' => 10,
        ]);

        // REQUIRED: participation provides the derived total_crafts
        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'current_crafts' => 10,
        ]);

        $this->assertTrue($this->service->shouldRestart($goal->fresh()));
    }

    public function test_returns_false_when_enchants_not_met(): void
    {
        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
            'max_kills' => null,
            'max_crafts' => null,
            'max_enchants' => 3,
        ]);

        // No participation row => totals remain 0
        $this->assertFalse($this->service->shouldRestart($goal->fresh()));
    }

    public function test_returns_true_when_all_configured_thresholds_are_met(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
            'max_kills' => 10,
            'max_crafts' => 5,
            'max_enchants' => 2,
        ]);

        // History (optional)
        $this->createGlobalEventKill([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'kills' => 10,
        ]);
        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'crafts' => 5,
        ]);
        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'enchants' => 2,
        ]);

        // REQUIRED: participation provides all derived totals
        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'current_kills' => 10,
            'current_crafts' => 5,
            'current_enchants' => 2,
        ]);

        $this->assertTrue($this->service->shouldRestart($goal->fresh()));
    }

    public function test_returns_false_when_one_of_multiple_thresholds_is_not_met(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
            'max_kills' => 10,
            'max_crafts' => 10,
            'max_enchants' => null,
        ]);

        // History (optional)
        $this->createGlobalEventKill([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'kills' => 10,
        ]);

        // Participation with crafts below threshold
        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'current_kills' => 10,
            'current_crafts' => 9,
        ]);

        $this->assertFalse($this->service->shouldRestart($goal->fresh()));
    }
}
