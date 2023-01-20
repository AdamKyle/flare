<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Handlers\Traits\DestroyKingdom;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class TooMuchPopulationHandler {

    use DestroyKingdom;

    /**
     * @var Kingdom|null $kingdom
     */
    private ?Kingdom $kingdom;

    /**
     * Set the kingdom.
     *
     * @param Kingdom $kingdom
     * @return $this
     */
    public function setKingdom(Kingdom $kingdom): TooMuchPopulationHandler {
        $this->kingdom = $kingdom;

        return $this;
    }

    /**
     * Get the kingdom.
     *
     * @return Kingdom|null
     */
    public function getKingdom(): ?Kingdom {
        return $this->kingdom;
    }

    /**
     * Handle the angry NPC.
     *
     * - Can take the gold from the treasury.
     * - Can take Gold Bars.
     * - Can Take Gold from the character.
     *
     * If the cost afterwords is still above 0: Destroy the kingdom.
     *
     * @return void
     */
    public function handleAngryNPC(): void {

        event(new GlobalMessageEvent('The Old Man stomps around! "You were warned! time to pay up!" '.
            'The kingdom at (X/Y): ' . $this->kingdom->x_position . '/' . $this->kingdom->y_position . ' on plane: ' . $this->kingdom->gameMap->name .
            ' is in trouble for being over populated.'
        ));

        $cost = $this->getTheCostOfTooMuchPopulation();

        $kingdomTreasury = $this->kingdom->treasury;
        $characterGold   = $this->kingdom->character->gold;
        $goldBars        = $this->kingdom->gold_bars;


        $cost = $this->takeAmountFromKingdomTreasury($kingdomTreasury, $cost);

        if ($cost === 0) {
            return;
        }

        $cost = $this->takeAmountFromGoldBars($goldBars, $cost);

        if ($cost === 0) {
            return;
        }

        $cost = $this->takeAmountFromCharacter($characterGold, $cost);

        if ($cost === 0) {
            return;
        }

        $this->destroyPlayerKingdom();
    }

    /**
     * Get the cost of population.
     *
     * Subtract the max amount from the current population and multiply that value by 10,000
     *
     * @return int
     */
    protected function getTheCostOfTooMuchPopulation(): int {
        $currentPop = $this->kingdom->current_population;
        $maxPop     = $this->kingdom->max_population;
        $currentPop = $currentPop - $maxPop;

        return $currentPop * 10000;
    }

    /**
     * Take the amount owed out of the kingdom treasury.
     *
     * Return any leftover cost.
     *
     * @param int $kingdomTreasury
     * @param int $cost
     * @return int
     */
    protected function takeAmountFromKingdomTreasury(int $kingdomTreasury, int $cost): int {
        $character = $this->kingdom->character;

        if ($kingdomTreasury <= 0 ) {
            return $cost;
        }

        if ($kingdomTreasury < $cost) {
            $this->kingdom->update([
                'treasury' => 0
            ]);

            event(new ServerMessageEvent($character->user,'The Old Man grumbles! ' .
                '"Now I have to take the rest out of your pockets, child!" (Not enough Treasury)'
            ));

            $this->kingdom = $this->kingdom->refresh();

            if ($kingdomTreasury <= 0) {
                return $cost;
            }

            return $cost - $kingdomTreasury;
        }

        $newTreasury = $kingdomTreasury - $cost;

        $this->kingdom->update([
            'treasury' => $newTreasury
        ]);

        event(new ServerMessageEvent($character->user,'The Old Man smiles! '.
            '"I am glad someone paid me." (The treasury was enough to wet his appetite)'
        ));

        $this->kingdom = $this->kingdom->refresh();

        return 0;
    }

    /**
     * Take the cost out the kingdoms gold bars.
     *
     * @param int $goldBars
     * @param int $cost
     * @return int
     */
    protected function takeAmountFromGoldBars(int $goldBars, int $cost): int {

        if ($goldBars <= 0) {
            return $cost;
        }

        $character  = $this->kingdom->character;
        $percentage = $cost / ($goldBars * 2000000000);

        if ($percentage < 0.01) {
            return $this->takeASingleGoldBar($character, $goldBars);
        }

        $newAmount = $goldBars - ceil($goldBars * $percentage);

        if ($newAmount <= 0) {
            return $this->takeAllGoldBars($character);
        }

        return $this->updateRemainingGoldBars($character, $newAmount, $percentage);
    }

    /**
     * Takes the cost from the character's gold.
     *
     * @param int $characterGold
     * @param int $cost
     * @return int
     */
    protected function takeAmountFromCharacter(int $characterGold, int $cost): int {

        $character = $this->kingdom->character;

        if ($cost > $characterGold) {
            $cost = $cost - $characterGold;

            $character->update([
                'gold' => 0,
            ]);

            event(new UpdateTopBarEvent($character->refresh()));

            event(new ServerMessageEvent($character->user,
                'The Old Man is not pleased (You do not have enough gold to pay him in full). ' .
                '"Child, you still owe me money! I shall take what is what is owed to me!"'
            ));

            return $cost;
        }

        $newGold = $characterGold - $cost;

        $character->update([
            'gold' => $newGold < 0 ? 0 : $newGold,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));

        event(new ServerMessageEvent($character->user,
            'The Old Man is pleased (you payed him the gold). ' .
            '"Make sure you learn a valuable lesson from this child!"'
        ));

        $this->kingdom = $this->kingdom->refresh();

        return 0;
    }

    /**
     * Destroy the kingdom.
     *
     * @return void
     */
    protected function destroyPlayerKingdom(): void {
        event(new GlobalMessageEvent(
            'The Old Man causes the ground to shake, the units to explode and the buildings to engulf in flames. '.
            'People are dying left, right and center as he Laughs. "I warned you, child!"'
        ));

        $character = $this->kingdom->character;

        $this->destroyKingdom($this->kingdom, $character);

        $character = $character->refresh;

        event(new UpdateGlobalMap($character));
        event(new AddKingdomToMap($character));

        $this->kingdom = null;
    }

    /**
     * Take a single gold bar from the kingdom.
     *
     * @param Character $character
     * @param int $goldBars
     * @return int
     */
    private function takeASingleGoldBar(Character $character, int $goldBars): int {
        $this->kingdom->update([
            'gold_bars' => $goldBars - 1,
        ]);

        event(new ServerMessageEvent($character->user,
            'The Old Man smiles! "A single gold bar is all I asked for." ' .
            '(The kingdoms gold bars were enough was enough to wet his appetite)'
        ));

        $this->kingdom = $this->kingdom->refresh();

        return 0;
    }

    /**
     * Take all the gold bars from the kingdom.
     *
     * @param Character $character
     * @return int
     */
    private function takeAllGoldBars(Character $character): int {
        $this->kingdom->update([
            'gold_bars' => 0,
        ]);

        event(new ServerMessageEvent($character->user,
            'The Old Man jumps from joy! "These are all mine now, child!" '.
            '(The kingdom lost all its Gold Bars. The Old Man is happy now... Win-Win?)'
        ));

        $this->kingdom = $this->kingdom->refresh();

        return 0;
    }

    /**
     * Update the kingdoms gold bars with what we have left.
     *
     * @param Character $character
     * @param int $newAmount
     * @param float $percentage
     * @return int
     */
    private function updateRemainingGoldBars(Character $character, int $newAmount, float $percentage): int {
        $this->kingdom->update([
            'gold_bars' => $newAmount
        ]);

        event(new ServerMessageEvent($character->user,
            'The Old Man grumbles! "Some of these are mine now..." '.
            '(The kingdom lost: '.($percentage * 100).'% Gold Bars. The Old Man is happy now... Win-Win?)'
        ));

        return 0;
    }

}
