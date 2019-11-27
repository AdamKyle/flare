<?php

namespace App\Game\Battle\Listeners;

use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Events\ShowTimeOutEvent;
use App\Game\Battle\Jobs\AttackTimeOutJob;


class AttackTimeOutListener
{

    public function __construct() {
    }

    /**
     * Handle the event.
     *
     * @param  \App\Game\Battle\UpdateCharacterEvent  $event
     * @return void
     */
    public function handle(AttackTimeOutEvent $event)
    {
        $event->character->update([
            'can_attack' => false
        ]);

        AttackTimeOutJob::dispatch($event->character)->delay(now()->addSeconds(11));

        broadcast(new ShowTimeOutEvent($event->character->user, true, false));
    }
}
