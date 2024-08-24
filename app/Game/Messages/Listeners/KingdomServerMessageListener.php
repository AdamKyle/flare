<?php

namespace App\Game\Messages\Listeners;

use App\Flare\Events\KingdomServerMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent as ServerMessage;
use Illuminate\Broadcasting\PendingBroadcast;

class KingdomServerMessageListener
{
    /**
     * Handle the event.
     */
    public function handle(KingdomServerMessageEvent $event): ?PendingBroadcast
    {
        return broadcast(new ServerMessage($event->user, $event->message));
    }
}
