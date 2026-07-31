<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\GlobalEventGoal;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\Events\Services\GlobalEventGoalProgressionService;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGlobalEventGoal;

class GlobalEventGoalProgressionServiceTest extends TestCase
{
    use CreateEvent, CreateGlobalEventGoal, RefreshDatabase;

    public function test_progressing_delusional_creates_next_goal_with_same_event_id_and_leaves_winter_goal_unchanged(): void
    {
        $delusionalEvent = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_goal_steps' => [GlobalEventSteps::BATTLE, GlobalEventSteps::CRAFT, GlobalEventSteps::ENCHANT],
            'current_event_goal_step' => GlobalEventSteps::BATTLE,
        ]);

        $delusionalGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $delusionalEvent->id,
            'max_kills' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
        ]);

        $winterEvent = $this->createEvent(['type' => EventType::WINTER_EVENT]);

        $winterGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $winterEvent->id,
            'max_kills' => 500,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->createGlobalEventKill([
            'global_event_goal_id' => $delusionalGoal->id,
            'character_id' => $character->id,
            'kills' => 10,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $delusionalGoal->id,
            'character_id' => $character->id,
            'current_kills' => 10,
        ]);

        resolve(GlobalEventGoalProgressionService::class)->advanceIfCurrentGoalComplete($delusionalGoal);

        $newGoal = GlobalEventGoal::where('event_type', EventType::DELUSIONAL_MEMORIES_EVENT)->first();

        $this->assertNotNull($newGoal);
        $this->assertEquals($delusionalEvent->id, $newGoal->event_id);
        $this->assertEquals(0, GlobalEventGoal::where('id', $delusionalGoal->id)->count());

        $this->assertEquals(500, $winterGoal->fresh()->max_kills);
        $this->assertEquals($winterEvent->id, $winterGoal->fresh()->event_id);
    }

    public function test_advance_if_current_goal_complete_returns_false_when_goal_not_complete(): void
    {
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT]);

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $event->id,
            'max_kills' => 500,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
        ]);

        $result = resolve(GlobalEventGoalProgressionService::class)->advanceIfCurrentGoalComplete($goal);

        $this->assertFalse($result);
    }
}
