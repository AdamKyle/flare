<?php

namespace App\Game\Messages\Listeners;

use App\Flare\Events\ServerMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent as ServerMessage;

class ServerMessageListener
{

    private $serverMessage;

    public function __construct(ServerMessageBuilder $serverMessage) {

        $this->serverMessage = $serverMessage;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Flare\Events\CreateCharacterEvent  $event
     * @return void
     */
    public function handle(ServerMessageEvent $event)
    {
        broadcast(new ServerMessage($event->user, $this->serverMessage->build($event->type)));
    }
}
