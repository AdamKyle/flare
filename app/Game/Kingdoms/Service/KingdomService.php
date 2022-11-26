<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Kingdom;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Core\Events\UpdateTopBarEvent;

class KingdomService {

    /**
     * @var UpdateKingdomHandler $updateKingdomHandle
     */
    private updateKingdomHandler $updateKingdomHandle;

    /**
     * @param UpdateKingdomHandler $updateKingdomHandler
     */
    public function __construct(UpdateKingdomHandler $updateKingdomHandler) {
        $this->updateKingdomHandle = $updateKingdomHandler;
    }

    /**
     * Embezzle from kingdom.
     *
     * @param Kingdom $kingdom
     * @param $amountToEmbezzle
     */
    public function embezzleFromKingdom(Kingdom $kingdom, $amountToEmbezzle) {
        $newMorale   = $kingdom->current_morale - 0.15;

        $kingdom->update([
            'treasury' => $kingdom->treasury - $amountToEmbezzle,
            'current_morale' => $newMorale,
        ]);

        $character = $kingdom->character;

        $newGold = $character->gold + $amountToEmbezzle;

        if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
            $newGold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update([
            'gold' => $newGold
        ]);

        $this->updateKingdomHandle->refreshPlayersKingdoms($character->refresh());

        event(new UpdateTopBarEvent($character->refresh()));
    }
}
