<?php

namespace App\Game\Messages\Listeners;

use Illuminate\Broadcasting\PendingBroadcast;
use App\Game\Messages\Events\ServerMessageEvent as ServerMessage;
use App\Flare\Events\KingdomServerMessageEvent;

class KingdomServerMessageListener
{
    /**
     * Handle the event.
     *
     * @param KingdomServerMessageEvent $event
     * @return PendingBroadcast|null
     */
    public function handle(KingdomServerMessageEvent $event): ?PendingBroadcast
    {

        switch($event->type) {
            case 'under-attack':
            case 'all-units-lost':
            case 'kingdom-attacked':
            case 'kingdom-taken':
            case 'units-returning':
            case 'units-returned':
            case 'units-recalled':
                return broadcast(new ServerMessage($event->user, $event->message));
            default:
                return null;
        }
    }
}
