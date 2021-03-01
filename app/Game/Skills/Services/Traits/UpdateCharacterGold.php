<?php

namespace App\Game\Skills\Services\Traits;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;

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
}