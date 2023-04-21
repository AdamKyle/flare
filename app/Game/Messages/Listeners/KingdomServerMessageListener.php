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
        return broadcast(new ServerMessage($event->user, $event->message));
    }
}
