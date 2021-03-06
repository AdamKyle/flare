<?php

namespace App\Game\Maps\Listeners;

use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Events\ShowTimeOutEvent;
use App\Game\Maps\Jobs\MoveTimeOutJob;

class MoveTimeOutListener
{
    /**
     * Handle the event.
     *
     * @param  \App\Game\Battle\UpdateCharacterEvent  $event
     * @return void
     */
    public function handle(MoveTimeOutEvent $event)
    {
        $character = $event->character;

        if ($event->timeOut !== 0) {
            $timeOut = now()->addMinutes($event->timeOut);

            $character->update([
                'can_move'          => false,
                'can_move_again_at' => $timeOut,
            ]);

            $character = $character->refresh();

            MoveTimeOutJob::dispatch($character)->delay($timeOut);
        } else {

            $timeOut = now()->addSeconds(10);

            $character->update([
                'can_move'          => false,
                'can_move_again_at' => $timeOut,
            ]);

            $character = $character->refresh();

            MoveTimeOutJob::dispatch($character)->delay($timeOut);
        }
        
        broadcast(new ShowTimeOutEvent($event->character->user, true, false, $event->timeOut, $event->setSail));
    }
}
