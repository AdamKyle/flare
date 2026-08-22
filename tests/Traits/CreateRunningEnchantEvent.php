<?php

namespace Tests\Traits;

use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;

trait CreateRunningEnchantEvent
{
    use CreateEvent, CreateGameMap, CreateGlobalEventGoal, CreateScheduledEvent;

    /**
     * @return array{0: Event, 1: GlobalEventGoal, 2: GameMap}
     */
    public function createRunningEnchantEvent(int $maxEnchants = 100): array
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
            'ends_at' => now()->addHour(),
        ]);

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_enchants' => $maxEnchants,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        return [$event, $goal, $gameMap];
    }
}
