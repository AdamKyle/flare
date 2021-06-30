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
     * @param ServerMessageEvent $event
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

                return broadcast(new ServerMessage($event->user, $message, false, true, $event->link, $event->id));
            case 'found_item':
                $message = 'You happen upon a: ' . $event->forMessage . '!';

                return broadcast(new ServerMessage($event->user, $message));
            case 'crafted':
                $message = 'You crafted a: ' . $event->forMessage . '!';

                return broadcast(new ServerMessage($event->user, $message));
            case 'enchantment_failed':
            case 'silenced':
            case 'deleted_affix':
            case 'deleted_item':
            case 'building-repair-finished':
            case 'building-upgrade-finished':
            case 'adventure':
            case 'sold_item':
            case 'new-building':
            case 'kingdom-resources-update':
            case 'unit-recruitment-finished':
            case 'plane-transfer':
            case 'enchanted':
            case 'moved-location':
                return broadcast(new ServerMessage($event->user, $event->forMessage));
            case 'failed_to_craft':
                $message = 'You failed to craft the item! You lost the investment.';

                return broadcast(new ServerMessage($event->user, $message));
            case 'new-skill':
                $message = 'You were given a new skill by The Creator. Head your character sheet to see the new skill: ' . $event->forMessage;

                return broadcast(new ServerMessage($event->user, $message));
            case 'new-damage-stat':
                $message = 'The Creator has changed your classes damage stat to: ' . $event->forMessage . '. Please adjust your gear accordingly for maximum damage.';

                return broadcast(new ServerMessage($event->user, $message));
            case 'disenchanted':
                $message = 'Disenchanted the item and got: ' . $event->forMessage . ' Gold Dust.';

                return broadcast(new ServerMessage($event->user, $message));
            case 'failed-to-disenchant':
                $message = 'Failed to disenchant the item, it shatters before you into ashes. You only got 1 Gold Dust for your efforts.';

                return broadcast(new ServerMessage($event->user, $message));
            case 'failed_to_transmute':
                $message = 'You failed to transmute the item. It melts into a pool of liquid gold dust before evaporating away. Wasted efforts!';

                return broadcast(new ServerMessage($event->user, $message));
            case 'transmuted':
                $message = 'You transmuted a new: ' . $event->forMessage . ' It shines with a powerful glow!';

                return broadcast(new ServerMessage($event->user, $message));
            case 'disenchanted-with-out-skill':
                $message = 'Disenchanted the item and got: ' . $event->forMessage . ' Gold Dust. No Disenchanting experience was given for destroying the item.';

                return broadcast(new ServerMessage($event->user, $message));
            default:
                return broadcast(new ServerMessage($event->user, $this->serverMessage->build($event->type)));
        }

    }
}
