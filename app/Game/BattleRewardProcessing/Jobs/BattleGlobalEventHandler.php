<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Values\MapNameValue;
use App\Game\BattleRewardProcessing\Handlers\BattleGlobalEventParticipationHandler;
use App\Game\Events\Values\EventType;


class BattleGlobalEventHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param integer $characterId
     */
    public function __construct(private int $characterId) {}

    /**
     * Handle the job
     *
     * @param BattleGlobalEventParticipationHandler $battleGlobalEventParticipationHandler
     * @return void
     */
    public function handle(BattleGlobalEventParticipationHandler $battleGlobalEventParticipationHandler): void
    {
        $character = Character::find($this->characterId);

        if (is_null($character)) {
            return;
        }

        $this->handleGlobalEventGoals($character, $battleGlobalEventParticipationHandler);
    }

    /**
     * Handle updating global events
     *
     * @param Character $character
     * @param BattleGlobalEventParticipationHandler $battleGlobalEventParticipationHandler
     * @return void
     */
    private function handleGlobalEventGoals(Character $character, BattleGlobalEventParticipationHandler $battleGlobalEventParticipationHandler)
    {
        $event = Event::whereIn('type', [
            EventType::WINTER_EVENT,
            EventType::DELUSIONAL_MEMORIES_EVENT,
        ])->first();

        if (is_null($event)) {
            return;
        }

        $globalEventGoal = GlobalEventGoal::where('event_type', $event->type)->first();

        $gameMapArrays = GameMap::whereIn('name', [
            MapNameValue::ICE_PLANE,
            MapNameValue::DELUSIONAL_MEMORIES,
        ])->pluck('id')->toArray();

        if (is_null($globalEventGoal) || ! in_array($character->map->game_map_id, $gameMapArrays)) {
            return;
        }

        $battleGlobalEventParticipationHandler->handleGlobalEventParticipation($character, $globalEventGoal);
    }
}
