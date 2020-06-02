<?php

namespace App\Game\Maps\Adventure\Listeners;

use App\Game\Maps\Adventure\Events\MoveTimeOutEvent;
use App\Game\Maps\Adventure\Events\ShowTimeOutEvent;
use App\Game\Maps\Adventure\Jobs\MoveTimeOutJob;

class MoveTimeOutListener
{

    public function __construct() {}

    /**
     * Handle the event.
     *
     * @param  \App\Game\Battle\UpdateCharacterEvent  $event
     * @return void
     */
    public function handle(MoveTimeOutEvent $event)
    {
        if ($event->timeOut !== 0) {
            MoveTimeOutJob::dispatch($event->character)->delay(now()->addMinutes($event->timeOut));
        } else {
            MoveTimeOutJob::dispatch($event->character)->delay(now()->addSeconds(10));
        }
        
        broadcast(new ShowTimeOutEvent($event->character->user, true, false, $event->timeOut, $event->setSail));
    }
}
