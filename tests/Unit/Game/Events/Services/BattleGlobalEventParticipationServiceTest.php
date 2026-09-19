<?php

namespace Tests\Unit\Game\Events\Services;

use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Contracts\BattleGlobalEventParticipation;
use App\Game\Events\Events\UpdateEventGoalCurrentProgressForCharacter;
use App\Game\Events\Events\UpdateEventGoalProgress;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Events\Values\ScheduledEventStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateScheduledEvent;

class BattleGlobalEventParticipationServiceTest extends TestCase
{
    use CreateEvent, CreateGameMap, CreateGlobalEventGoal, CreateScheduledEvent, RefreshDatabase;

    public function test_participate_is_a_noop_when_the_character_has_no_applicable_event(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = resolve(BattleGlobalEventParticipation::class)->participate($character->id, 1);

        $this->assertFalse($result->participated());
        $this->assertSame(0, $result->appliedKillCount());
    }

    public function test_participate_is_ineligible_for_a_delusional_event_not_on_the_battle_step(): void
    {
        Event::fake();
        $schedule = $this->createScheduledEvent([
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
            'max_kills' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);
        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $result = resolve(BattleGlobalEventParticipation::class)->participate($character->id, 1);

        $this->assertFalse($result->participated());
    }

    public function test_participate_applies_kill_count_and_broadcasts_progress(): void
    {
        Event::fake();
        $schedule = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);
        $event = $this->createEvent([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $schedule->id,
            'ends_at' => now()->addHour(),
        ]);
        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $event->id,
            'max_kills' => 100,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE->value,
        ]);
        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::WINTER_EVENT]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $result = resolve(BattleGlobalEventParticipation::class)->participate($character->id, 3);

        $this->assertTrue($result->participated());
        $this->assertSame(3, $result->appliedKillCount());
        $killsRow = $character->globalEventKills()->where('global_event_goal_id', $goal->id)->first();
        $this->assertSame(3, $killsRow->kills);
        Event::assertDispatched(UpdateEventGoalProgress::class);
        Event::assertDispatched(UpdateEventGoalCurrentProgressForCharacter::class);
    }

    public function test_participate_caps_applied_kill_count_to_the_goals_remaining_kills(): void
    {
        Event::fake();
        $schedule = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);
        $event = $this->createEvent([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $schedule->id,
            'ends_at' => now()->addHour(),
        ]);
        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $event->id,
            'max_kills' => 10,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE->value,
        ]);
        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::WINTER_EVENT]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();
        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'current_kills' => 8,
        ]);
        $this->createGlobalEventKill([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
            'kills' => 8,
        ]);

        $result = resolve(BattleGlobalEventParticipation::class)->participate($character->id, 5);

        $this->assertSame(2, $result->appliedKillCount());
        $killsRow = $character->globalEventKills()->where('global_event_goal_id', $goal->id)->first();
        $this->assertSame(10, $killsRow->kills);
    }

    public function test_participate_rewards_participants_when_a_reward_threshold_is_crossed(): void
    {
        Event::fake();
        $schedule = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);
        $event = $this->createEvent([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $schedule->id,
            'ends_at' => now()->addHour(),
        ]);
        $this->createGlobalEventGoal([
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $event->id,
            'max_kills' => 100,
            'reward_every' => 5,
            'next_reward_at' => 5,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE->value,
        ]);
        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::WINTER_EVENT]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $result = resolve(BattleGlobalEventParticipation::class)->participate($character->id, 5);

        $this->assertSame(1, $result->thresholdRewardsProcessed());
    }

    public function test_participate_advances_a_stepped_goal_that_just_completed(): void
    {
        Event::fake();
        $schedule = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
        ]);
        $event = $this->createEvent([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $schedule->id,
            'ends_at' => now()->addHour(),
            'event_goal_steps' => [GlobalEventSteps::BATTLE],
            'current_event_goal_step' => GlobalEventSteps::BATTLE,
        ]);
        $this->createGlobalEventGoal([
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $event->id,
            'max_kills' => 5,
            'reward_every' => 5,
            'next_reward_at' => 5,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE->value,
        ]);
        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::WINTER_EVENT]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $result = resolve(BattleGlobalEventParticipation::class)->participate($character->id, 5);

        $this->assertTrue($result->goalAdvanced());
    }
}
