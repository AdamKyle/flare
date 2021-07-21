<?php

namespace App\Game\Skills\Services\Traits;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Item;

trait UpdateCharacterGold {

    /**
     * Update the characters gold when enchanting.
     *
     * Subtract cost from gold.
     *
     * @param Character $character
     * @param int $cost
     * @return void
     */
    public function updateCharacterGold(Character $character, int $cost): void {
        $character->update([
            'gold' => $character->gold - $cost,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));
    }

    /**
     * Update the alchemy currencies
     *
     * @param Character $character
     * @param Item $item
     */
    public function updateAlchemyCost(Character $character, Item $item): void {
        $character->update([
            'gold_dust'  => ($character->gold_dust - $item->gold_dust_cost),
            'shards'     => ($character->shards - $item->shards_cost),
        ]);

        event(new UpdateTopBarEvent($character->refresh()));
    }

    /**
     * Only really called if something goes wrong.
     *
     * @param Character $character
     * @param int $cost
     */
    public function giveGoldBack(Character $character, int $cost): void {
        $character->update([
            'gold' => $character->gold + $cost,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));
    }
}
