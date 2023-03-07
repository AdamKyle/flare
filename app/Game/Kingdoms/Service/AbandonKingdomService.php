<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Events\UpdateNPCKingdoms;
use App\Game\Kingdoms\Handlers\GiveKingdomsToNpcHandler;
use App\Game\Kingdoms\Jobs\KingdomSettlementLockout;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class AbandonKingdomService {

    /**
     * @var UpdateKingdom $updateKingdom
     */
    private UpdateKingdom $updateKingdom;

    /**
     * @var GiveKingdomsToNpcHandler $giveKingdomsToNpcHandler
     */
    private GiveKingdomsToNpcHandler $giveKingdomsToNpcHandler;

    /**
     * @var Kingdom $kingdom
     */
    private Kingdom $kingdom;

    /**
     * @var Character $character
     */
    private Character $character;

    /**
     * @param UpdateKingdom $updateKingdom
     * @param GiveKingdomsToNpcHandler $giveKingdomsToNpcHandler
     */
    public function __construct(UpdateKingdom $updateKingdom, GiveKingdomsToNpcHandler $giveKingdomsToNpcHandler) {
        $this->updateKingdom            = $updateKingdom;
        $this->giveKingdomsToNpcHandler = $giveKingdomsToNpcHandler;
    }

    /**
     * Set the kingdom and it's character.
     *
     * @param Kingdom $kingdom
     * @return $this
     */
    public function setKingdom(Kingdom $kingdom): AbandonKingdomService {
        $this->kingdom   = $kingdom;
        $this->character = $kingdom->character;

        return $this;
    }

    /**
     * Abandon the kingdom.
     *
     * - Reduce attributes
     * - Time the player out from settling again.
     * - Give the kingdom to the NPC.
     *
     * - Alerts the players of a new NPC kingdom.
     * - Alerts the player their kingdom hs been abandoned.
     *
     * @return void
     */
    public function abandon(): void {
        $character = $this->kingdom->character;

        $this->reduceAttributes();
        $this->setTimeOut();

        $kingdom = $this->kingdom;

        $this->giveKingdomsToNpcHandler->giveKingdomToNPC($kingdom);

        event(new GlobalMessageEvent('A kingdom has fallen into the rubble at (X/Y): ' .
            $kingdom->x_position . '/' . $kingdom->y_position . ' on the: ' .
            $kingdom->gameMap->name .' plane.'
        ));

        $message = $kingdom->name . ' Has been given to the NPC due to being abandoned, at Location (x/y): '
            . $kingdom->x_position . '/' . $kingdom->y_position . ' on the: ' . $kingdom->gameMap->name . ' plane.';

        ServerMessageHandler::handleMessage($this->character->user, 'kingdom_resources_update', $message);

        $this->updateKingdom->updateKingdomAllKingdoms($character);

        event(new UpdateNPCKingdoms($this->kingdom->gameMap));
    }

    /**
     * Reduce kingdom attributes.
     *
     * Includes:
     *
     * - morale
     * - population
     * - resources
     * - treasury
     *
     * @return void
     */
    protected function reduceAttributes(): void {
        $this->kingdom->update([
            'current_morale'     => 0.10,
            'current_population' => 0,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'treasury'           => 0,
            'protected_until'    => null,
        ]);

        $this->kingdom = $this->kingdom->refresh();
    }

    /**
     * Don't allow the player to settle or purchase another kingdom for x minutes.
     *
     * - Stacks if they abandon multiple.
     *
     * @return void
     */
    protected function setTimeOut(): void {
        if (!is_null($this->character->can_settle_again_at)) {
            $time = $this->character->can_settle_again_at->addMinutes(15);
        } else {
            $time = now()->addMinutes(15);
        }

        $this->character->update([
            'can_settle_again_at' => $time
        ]);

        $this->character = $this->character->refresh();

        KingdomSettlementLockout::dispatch($this->character)->delay($time);

        $minutes = now()->diffInMinutes($time);

        event(new ServerMessageEvent($this->character->user, 'You have been locked out of settling or purchasing a new kingdom for: '.
            $minutes . ' Minutes. If you abandon another kingdom, we add 15 minutes to what ever time is left.
            If you attempt to settle or purchase a king you will be told how much time you have left.'));
    }

}
