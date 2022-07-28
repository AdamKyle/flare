<?php

namespace App\Game\Kingdoms\Service;


use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Handlers\GiveKingdomsToNpcHandler;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class KingdomUpdateService {

    /**
     * @var Kingdom
     */
    private Kingdom $kingdom;

    /**
     * @var Character|null $character
     */
    private ?Character $character;

    /**
     * @var GiveKingdomsToNpcHandler  $giveKingdomsToNpcHandler
     */
    private GiveKingdomsToNpcHandler $giveKingdomsToNpcHandler;

    /**
     * @param GiveKingdomsToNpcHandler $giveKingdomsToNpcHandler
     */
    public function __construct(GiveKingdomsToNpcHandler $giveKingdomsToNpcHandler) {
        $this->giveKingdomsToNpcHandler = $giveKingdomsToNpcHandler;
    }

    /**
     * Sets the kingdom.
     *
     * - Grabs the character from the kingdom, assuming the kingdom is not npc owned.
     *
     * @param Kingdom $kingdom
     * @return $this
     */
    public function setKingdom(Kingdom $kingdom): KingdomUpdateService {
        $this->kingdom = $kingdom;

        if (!$this->kingdom->npc_owned) {
            $this->character = $this->kingdom->character;
        }

        return $this;
    }

    public function updateKingdom(): void {

        if (is_null($this->character)) {
            return;
        }

        if ($this->shouldGiveKingdomToNpc()) {
            $this->giveKingdomsToNpcHandler->giveKingdomToNPC($this->kingdom);

            event(new ServerMessageEvent($this->character->user, 'Your kingdom has been given over to the NPC: The Old Man. Kingdom has not been walked in 30 days or more.'));

            $gameMapName = $this->kingdom->gameMap->name;
            $xPosition   = $this->kingdom->x_position;
            $yPosition   = $this->kingdom->y_position;

            event(new GlobalMessageEvent('A kingdom on: ' . $gameMapName . 'at (X/Y): ' . $xPosition . '/' . $yPosition . ' has been neglected. The Old Man has taken it (New NPC Kingdom up for grabs).'));

            return;
        }

        dump('Is the old man angry? (over populated).');

        dump('Update Morale.');

        dump('Update Treasury.');

        dump('Update resources.');
    }

    /**
     * Should we give the kingdom to the NPC?
     *
     * - If the kingdom is not NPC Owned and never been walked, then yes, hand it over.
     * - If the kingdom has been walked and is not NPC owned, has it been walked in the last 30 days?
     *   - If the date since last walked is equal to or greater than 30 days, than hand it over.
     *
     * @return bool
     */
    public function shouldGiveKingdomToNpc(): bool {
        if (!$this->kingdom->npc_owned && is_null($this->kingdom->last_walked)) {
            return true;
        }

        if (!$this->kingdom->npc_owned && !is_null($this->kingdom->last_walked)) {
            $lastTimeWalked = $this->getLastTimeWalked();

            if ($lastTimeWalked >= 30) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the last time walked in days.
     *
     * @return int
     */
    public function getLastTimeWalked(): int {
        return $this->kingdom->last_walked->diffInDays(now());
    }

    public function updateKingdomMorale(): void {

    }

}
