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
            'message'      => $event->adventureLog->complete ? $event->adventureLog->adventure->name . ' Has been completed! Click to collect your rewards!' : 'You have died during the adventure. Please revive and review.',
            'status'       => $event->adventureLog->complete ? 'success' : 'failed',
            'type'         => 'adventure',
            'url'          => route('game.current.adventure'),
            'adventure_id' => $event->adventureLog->adventure->id,
        ]);

        event(new UpdateNotificationsBroadcastEvent($event->adventureLog->character->notifications()->where('read', false)->get(), $event->adventureLog->character->user));
    }
}
