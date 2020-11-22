<?php

namespace App\Game\Messages\Listeners;

use App\Flare\Events\ServerMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent as ServerMessage;
use App\Game\Messages\Builders\ServerMessageBuilder;

class ServerMessageListener
{

    /**
     * @var ServerMessage $serverMessage
     */
    private $serverMessage;

    /**
     * Constructor
     * 
     * @param ServerMessage $serverMessage
     * @return void
     */
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
            case 'enchanted':
                return broadcast(new ServerMessage($event->user, $event->forMessage));
            case 'enchantment_failed':
                return broadcast(new ServerMessage($event->user, $event->forMessage));
            case 'failed_to_craft':
                $message = 'You failed to craft the item! You lost the investment.';

                return broadcast(new ServerMessage($event->user, $message));
            case 'adventure':
                return broadcast(new ServerMessage($event->user, $event->forMessage));
            case 'deleted_item':
                return broadcast(new ServerMessage($event->user, $event->forMessage));
            case 'deleted_affix':
                return broadcast(new ServerMessage($event->user, $event->forMessage));
            case 'silenced':
                return broadcast(new ServerMessage($event->user, $event->forMessage));
            case 'new-skill':
                $message = 'You were given a new skill by The Creator. Head your character sheet to see the new skill: ' . $event->forMessage; 
                
                return broadcast(new ServerMessage($event->user, $message));
            case 'new-damage-stat':
                $message = 'The Creator has changed your classes damage stat to: ' . $event->forMessage . '. Please adjust your gear accordingly for maximum damage.'; 
                
                return broadcast(new ServerMessage($event->user, $message));
            default:
                return broadcast(new ServerMessage($event->user, $this->serverMessage->build($event->type)));
        }

    }
}
