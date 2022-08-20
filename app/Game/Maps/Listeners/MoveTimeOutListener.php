<?php

namespace App\Game\Maps\Listeners;

use App\Flare\Models\Character;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Events\ShowTimeOutEvent;
use App\Game\Maps\Jobs\MoveTimeOutJob;

class MoveTimeOutListener {

    /**
     * Handle the event.
     *
     * @param MoveTimeOutEvent $event
     * @return void
     */
    public function handle(MoveTimeOutEvent $event): void {
        $character = $event->character;

        if ($event->traverse) {
            $time = $event->timeOut;

            MoveTimeOutJob::dispatch($character->id)->delay($time);
        } else if ($event->timeOut !== 0) {
            $time = $this->disPatchMinuteBasedMovementTimeout($event, $character);
        } else {
            $this->dispatchWalkingTimeOut($event, $character);

            $time = 10;
        }

        event(new ShowTimeOutEvent($event->character->user, true, false, $time, $event->setSail));
    }

    /**
     * Dispatches the minute based movement timeout.
     *
     * - Applies skill bonus reductions to time.
     *
     * @param MoveTimeOutEvent $event
     * @param Character $character
     * @return int
     */
    protected function disPatchMinuteBasedMovementTimeout(MoveTimeOutEvent $event, Character $character): int {
        $time = (int) round($event->timeOut - ($event->timeOut * $this->findMovementMinuteTimeReduction($character)));

        if ($time < 1) {
            $timeOut    = now()->addMinute();
        } else {
            $timeOut    = now()->addMinutes($time);
        }

        $character->update([
            'can_move'          => false,
            'can_move_again_at' => $timeOut,
        ]);

        $character = $character->refresh();

        MoveTimeOutJob::dispatch($character->id)->delay($timeOut);

        return $time * 60;
    }

    /**
     * Dispatches the walking movement timer.
     *
     * @param MoveTimeOutEvent $event
     * @param Character $character
     * @return void
     */
    protected function dispatchWalkingTimeOut(MoveTimeOutEvent $event, Character $character): void {
        $timeOut = now()->addSeconds(10);

        $character->update([
            'can_move'          => false,
            'can_move_again_at' => $timeOut,
        ]);

        $character = $character->refresh();

        MoveTimeOutJob::dispatch($character->id)->delay($timeOut);
    }

    /**
     * Finds the characters moment timeout reduction.
     *
     * @param Character $character
     * @return float
     */
    protected function findMovementMinuteTimeReduction(Character $character): float {
        $skill = $character->skills->filter(function($skill) {
            return $skill->type()->isMovementTimer();
        })->first();

        if (is_null($skill)) {
            return 0;
        }

        return $skill->move_time_out_mod;
    }
}
