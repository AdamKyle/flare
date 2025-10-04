<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Events\Events\UpdateEventGoalProgress;
use App\Game\Events\Services\EventParticipantNotifierService;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGlobalEventGoal;

class EventParticipantNotifierServiceTest extends TestCase
{
    use CreateGameMap, CreateGlobalEventGoal, RefreshDatabase;

    public function test_does_nothing_when_participants_count_is_zero(): void
    {
        EventFacade::fake([UpdateEventGoalProgress::class, UpdateCharacterStatus::class]);

        $goal = $this->createGlobalEventGoal([
            'max_kills' => null,
            'reward_every' => 100,
            'next_reward_at' => 10,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->app->make(EventParticipantNotifierService::class)
            ->notifyForGoal($goal, 0);

        EventFacade::assertNothingDispatched();
    }

    public function test_does_nothing_when_no_participation_rows_exist(): void
    {
        EventFacade::fake([UpdateEventGoalProgress::class, UpdateCharacterStatus::class]);

        $this->createGameMap([
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $goal = $this->createGlobalEventGoal([
            'max_kills' => null,
            'reward_every' => 100,
            'next_reward_at' => 10,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->app->make(EventParticipantNotifierService::class)
            ->notifyForGoal($goal, 1);

        EventFacade::assertNothingDispatched();
    }

    public function test_does_nothing_when_no_map_exists_for_event_type(): void
    {
        EventFacade::fake([UpdateEventGoalProgress::class, UpdateCharacterStatus::class]);

        $char = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $goal = $this->createGlobalEventGoal([
            'max_kills' => null,
            'reward_every' => 100,
            'next_reward_at' => 10,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        // One participation row but no map created for this event type:
        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id' => $char->id,
            'current_kills' => 0,
            'current_crafts' => 0,
            'current_enchants' => 0,
        ]);

        $this->app->make(EventParticipantNotifierService::class)
            ->notifyForGoal($goal, 1);

        EventFacade::assertNothingDispatched();
    }

    public function test_notifies_unique_participants_with_aggregated_totals(): void
    {
        EventFacade::fake([UpdateEventGoalProgress::class, UpdateCharacterStatus::class]);

        $this->createGameMap([
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $charA = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $charB = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $goal = $this->createGlobalEventGoal([
            'max_kills' => null,
            'reward_every' => 100,
            'next_reward_at' => 10,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        // Duplicate participation rows for A + one for B (de-dup should still notify twice)
        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id' => $charA->id,
            'current_kills' => 0,
            'current_crafts' => 0,
            'current_enchants' => 0,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id' => $charA->id,
            'current_kills' => 0,
            'current_crafts' => 0,
            'current_enchants' => 0,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id' => $charB->id,
            'current_kills' => 0,
            'current_crafts' => 0,
            'current_enchants' => 0,
        ]);

        // Aggregates: A gets 10+15=25 kills and 2 crafts; B gets 6 crafts (5+1) and 5 enchants (2+3)
        $this->createGlobalEventKill([
            'global_event_goal_id' => $goal->id,
            'character_id' => $charA->id,
            'kills' => 10,
        ]);
        $this->createGlobalEventKill([
            'global_event_goal_id' => $goal->id,
            'character_id' => $charA->id,
            'kills' => 15,
        ]);

        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $goal->id,
            'character_id' => $charA->id,
            'crafts' => 2,
        ]);
        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $goal->id,
            'character_id' => $charB->id,
            'crafts' => 5,
        ]);
        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $goal->id,
            'character_id' => $charB->id,
            'crafts' => 1,
        ]);

        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $goal->id,
            'character_id' => $charB->id,
            'enchants' => 2,
        ]);
        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $goal->id,
            'character_id' => $charB->id,
            'enchants' => 3,
        ]);

        $participantsCount = 2;

        $this->app->make(EventParticipantNotifierService::class)
            ->notifyForGoal($goal, $participantsCount);

        EventFacade::assertDispatched(UpdateEventGoalProgress::class, 2);
        EventFacade::assertDispatched(UpdateCharacterStatus::class, 2);
    }
}
