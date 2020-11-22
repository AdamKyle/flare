<?php

namespace App\Game\Core\Listeners;

use App\Flare\Models\Notification as Notification;
use App\Game\Core\Events\CreateAdventureNotificationEvent;
use App\Game\Core\Events\UpdateNotificationsBroadcastEvent;

class CreateAdventureNotificationListener
{
    public function handle(CreateAdventureNotificationEvent $event)
    {
        Notification::create([
            'character_id' => $event->adventureLog->character_id,
            'title'        => $event->adventureLog->adventure->name,
            'message'      => $event->adventureLog->complete ? 'Adventure Has been completed! Click to collect your rewards!' : 'You have died but have rewards. Please revive before collecting them.',
            'status'       => $event->adventureLog->complete ? 'success' : 'failed',
            'type'         => 'adventure',
            'url'          => route('game.current.adventure'),
            'adventure_id' => $event->adventureLog->adventure->id,
        ]);

        event(new UpdateNotificationsBroadcastEvent($event->adventureLog->character->notifications()->where('read', false)->get(), $event->adventureLog->character->user));
    }
}
