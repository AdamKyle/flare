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

    public function updateAlchemyCost(Character $character, Item $item): void {
        $character->update([
            'gold_dust'  => $character->gold_dust - $item->gold_dust_cost,
            'shard_cost' => $character->shards - $item->shard_cost,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));
    }
}
