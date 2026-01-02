<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Values\UnitCosts;

class PurchasePeopleService
{
    private UpdateKingdom $updateKingdom;

    private Kingdom $kingdom;

    public function __construct(UpdateKingdom $updateKingdom)
    {
        $this->updateKingdom = $updateKingdom;
    }

    /**
     * Sets the kingdom.
     *
     * @return $this
     */
    public function setKingdom(Kingdom $kingdom): PurchasePeopleService
    {
        $this->kingdom = $kingdom;

        return $this;
    }

    /**
     * Purchases people.
     *
     * - Updates the character gold
     * - Updates the kingdom population.
     */
    public function purchasePeople(int $amountToPurchase): void
    {

        $character = $this->updateCharacterGold($amountToPurchase);

        $amountToBuy = $this->getAmountToPurchase($amountToPurchase);

        $this->kingdom->update([
            'current_population' => $amountToBuy,
        ]);

        event(new UpdateCharacterBaseDetailsEvent($character->refresh()));

        $this->updateKingdom->updateKingdom($this->kingdom->refresh());
    }

    protected function getAmountToPurchase(int $amountToPurchase): int
    {
        $amountToBuy = $amountToPurchase;

        if ($amountToBuy > KingdomMaxValue::MAX_CURRENT_POPULATION) {
            $amountToBuy = KingdomMaxValue::MAX_CURRENT_POPULATION;
        }

        $amountToBuy = $this->kingdom->current_population + $amountToBuy;

        if ($amountToBuy > KingdomMaxValue::MAX_CURRENT_POPULATION) {
            $amountToBuy = KingdomMaxValue::MAX_CURRENT_POPULATION;
        }

        return $amountToBuy;
    }

    protected function updateCharacterGold(int $amountToPurchase): Character
    {
        $character = $this->kingdom->character;

        $newGold = $character->gold - (new UnitCosts(UnitCosts::PERSON))->fetchCost() * $amountToPurchase;

        $character->update([
            'gold' => $newGold,
        ]);

        return $character->refresh();
    }
}
