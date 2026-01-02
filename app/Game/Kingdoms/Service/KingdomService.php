<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Kingdom;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;

class KingdomService
{
    private updateKingdomHandler $updateKingdomHandle;

    public function __construct(UpdateKingdomHandler $updateKingdomHandler)
    {
        $this->updateKingdomHandle = $updateKingdomHandler;
    }

    /**
     * Embezzle from kingdom.
     */
    public function embezzleFromKingdom(Kingdom $kingdom, $amountToEmbezzle)
    {
        $newMorale = $kingdom->current_morale - 0.15;

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
            'gold' => $newGold,
        ]);

        $this->updateKingdomHandle->refreshPlayersKingdoms($character->refresh());

        event(new UpdateCharacterBaseDetailsEvent($character->refresh()));
    }
}
