<?php

namespace App\Game\Maps\Listeners;

use App\Flare\Models\Character;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Events\ShowTimeOutEvent;
use App\Game\Maps\Jobs\MoveTimeOutJob;

class MoveTimeOutListener
{

    private $time;

    /**
     * Handle the event.
     *
     * @param  \App\Game\Battle\UpdateCharacterEvent  $event
     * @return void
     */
    public function handle(MoveTimeOutEvent $event)
    {
        $character = $event->character;
        $this->time = $event->timeOut;

        if ($event->timeOut !== 0) {
            $time = $event->timeOut - ($event->timeOut * $this->findMovementMinuteTimeReduction($character));

            if ($time <= 0.0) {
                $timeOut    = now()->addMinute();
                $this->time = 1;
            } else {
                $timeOut    = now()->addMinutes($time);
                $this->time = $time;
            }

            $character->update([
                'can_move'          => false,
                'can_move_again_at' => $timeOut,
            ]);

            $character = $character->refresh();

            MoveTimeOutJob::dispatch($character)->delay($timeOut);
        } else {
            $time = 10 - (10 * $this->findMovementTimeReductions($character));

            if ($this->time < 1) {
                $this->time = 1;
            }

            $timeOut = now()->addSeconds($this->time);

            $character->update([
                'can_move'          => false,
                'can_move_again_at' => $timeOut,
            ]);

            $character = $character->refresh();

            MoveTimeOutJob::dispatch($character)->delay($timeOut);
        }

        broadcast(new ShowTimeOutEvent($event->character->user, true, false, $this->time, $event->setSail));
    }

    protected function findMovementTimeReductions(Character $character) {
        $skill = $character->skills->filter(function($skill) {
            return $skill->type()->isMovementTimer();
        })->first();

        if (is_null($skill)) {
            return 0;
        }

        return $skill->move_time_out_mod;
    }

    protected function findMovementMinuteTimeReduction(Character $character) {
        $skill = $character->skills->filter(function($skill) {
            return $skill->type()->isMovementTimer();
        })->first();

        if (is_null($skill)) {
            return 0;
        }

        return $skill->move_time_out_mod;
    }
}
