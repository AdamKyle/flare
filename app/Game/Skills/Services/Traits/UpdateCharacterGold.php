<?php

namespace App\Game\Skills\Services\Traits;

use App\Game\Core\Events\UpdateTopBarEvent;
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
     * Update character copper coins and gold dust.
     *
     * @param Character $character
     * @param Item $item
     * @return void
     */
    public function updateTrinketCost(Character $character, Item $item): void {
        $character->update([
            'copper_coins'  => ($character->copper_coins - $item->copper_coin_cost),
            'gold_dust'     => ($character->gold_dust - $item->gold_dust_cost),
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
}
