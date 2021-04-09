<?php

namespace App\Game\Messages\Listeners;

use App\Game\Messages\Events\ServerMessageEvent as ServerMessage;
use App\Flare\Events\KingdomServerMessageEvent;

class KingdomServerMessageListener
{
    /**
     * Handle the event.
     *
     * @param  \App\Flare\Events\CreateCharacterEvent  $event
     * @return void
     */
    public function handle(KingdomServerMessageEvent $event)
    {

        switch($event->type) {
            case 'all-units-lost':
                return broadcast(new ServerMessage($event->user, $event->message));
            default:
                return null;
        }
    }
}
