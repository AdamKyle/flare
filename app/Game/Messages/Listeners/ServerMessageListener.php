<?php

namespace App\Game\Messages\Listeners;

use App\Flare\Events\ServerMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent as ServerMessage;
use App\Game\Messages\Builders\ServerMessageBuilder;

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
        switch($event->type) {
            case 'level_up':
                $message = 'You are now level: ' . $event->user->character->level . '!';

                return broadcast(new ServerMessage($event->user, $message));
            case 'gold_rush':
                $message = 'Gold Rush! You gained: ' . $event->forMessage . ' Gold!';

                return broadcast(new ServerMessage($event->user, $message));
            case 'gained_item':
                $message = 'You found a: ' . $event->forMessage . ' on the enemies corpse!';

                return broadcast(new ServerMessage($event->user, $message));
            case 'found_item':
                $message = 'You happen upon a: ' . $event->forMessage . '!';

                return broadcast(new ServerMessage($event->user, $message));
            case 'crafted':
                $message = 'You crafted a: ' . $event->forMessage . '!';

                return broadcast(new ServerMessage($event->user, $message));
            case 'failed_to_craft':
                $message = 'You failed to craft the item! You lost the investment. Youll still gain experience towards crafting.';

                return broadcast(new ServerMessage($event->user, $message));
            default:
                return broadcast(new ServerMessage($event->user, $this->serverMessage->build($event->type)));
        }

    }
}
