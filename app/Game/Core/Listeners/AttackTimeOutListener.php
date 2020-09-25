<?php

namespace App\Game\Core\Listeners;

use App\Game\Core\Events\AttackTimeOutEvent;
use App\Game\Core\Events\ShowTimeOutEvent;
use App\Game\Core\Jobs\AttackTimeOutJob;


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
        $time = $event->character->is_dead ? 20 : 10;

        $event->character->update([
            'can_attack'          => false,
            'can_attack_again_at' => now()->addSeconds($time),
        ]);

        // broadcast(new ShowTimeOutEvent($event->character->user, true, false, $time));

        AttackTimeOutJob::dispatch($event->character)->delay(now()->addSeconds($time));
    }
}
