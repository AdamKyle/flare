<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Kingdoms\Values\KingdomMaxValue;

class SettlerHandler {

    use KingdomCache;

    /**
     * @var array $newAttackingUnits
     */
    private array $newAttackingUnits = [];

    /**
     * Get new attacking units.
     *
     * - Could be an empty array.
     *
     * @return array
     */
    public function getNewAttackingUnits(): array {
        return $this->newAttackingUnits;
    }

    /**
     * Attempt to take the defending character's kingdom.
     *
     * @param Kingdom $attackingKingdom
     * @param Kingdom $defendingKingdom
     * @param array $attackingUnits
     * @return Kingdom
     */
    public function attemptToSettleKingdom(Kingdom $attackingKingdom, Kingdom $defendingKingdom, array $attackingUnits): Kingdom {
        $settlerData      = $this->findSettler($attackingUnits);
        $otherUnitsAmount = $this->getAmountOfOtherUnits($attackingUnits);

        if (is_null($settlerData)) {
            return $defendingKingdom;
        }

        if ($settlerData['amount'] === 0) {
            return $defendingKingdom;
        }

        if ($otherUnitsAmount === 0) {
            $this->updateAttackingUnits($attackingUnits);

            return $defendingKingdom;
        }

        $reducesMoraleBy = $this->getReducesMoraleBy($attackingKingdom, $settlerData['unit_id']);

        $currentMorale   = $defendingKingdom->current_morale;

        $currentMorale   = $currentMorale - $reducesMoraleBy;

        if ($currentMorale <= 0) {
            $originalOwner = $defendingKingdom->character;

            $defendingKingdom->update([
                'character_id'   => $attackingKingdom->character->id,
                'current_morale' => 0.10
            ]);

            $defendingKingdom = $defendingKingdom->refresh();

            $this->removeKingdomFromCache($originalOwner, $defendingKingdom);
            $this->addKingdomToCache($defendingKingdom->character, $defendingKingdom);

            event(new UpdateGlobalMap($attackingKingdom->character));

            return $this->updateNewKingdom($defendingKingdom, $attackingUnits);
        } else {
            $defendingKingdom->update([
                'current_morale' => $currentMorale,
            ]);
        }

        return $defendingKingdom->refresh();
    }

    protected function findSettler(array $attackingUnits): ?array {
        $index = array_search('Settler', array_column($attackingUnits, 'name'));

        if ($index !== false) {
            return $attackingUnits[$index];
        }

        return null;
    }

    protected function getReducesMoraleBy(Kingdom $attackingKingdom, int $unitId): float {
        return $attackingKingdom->units()
                                ->where('id', $unitId)
                                ->where('is_settler', true)
                                ->first()
                                ->reduces_morale_by;
    }

    protected function updateAttackingUnits(array $attackingUnits): void {
        $index = array_search('Settler', array_column($attackingUnits, 'name'));

        if ($index !== false) {
            $attackingUnits[$index]['amount'] = 0;
        }

        $this->newAttackingUnits = $attackingUnits;
    }

    protected function getAmountOfOtherUnits(array $attackingUnits): int {
        $amount = 0;

        foreach ($attackingUnits as $unitData) {
            if ($unitData['name'] === 'Settler') {
                continue;
            }

            $amount += $unitData['amount'];
        }

        return $amount;
    }

    protected function updateNewKingdom(Kingdom $defendingKingdom, array $attackingUnits): Kingdom {

        $kingdomsUnits = $defendingKingdom->units;

        foreach ($attackingUnits as $unitData) {

            if ($unitData['amount'] === 0 || $unitData['name'] === 'Settler') {
                continue;
            }

            $foundUnit = $kingdomsUnits->filter(function($kingdomUnit) use ($unitData) {
                return $kingdomUnit->gameUnit->name === $unitData['name'];
            })->first();

            if (is_null($foundUnit)) {
                $gameUnitId = GameUnit::where('name', $unitData['name'])->first()->id;

                $defendingKingdom->units()->create([
                    'kingdom_id'   => $defendingKingdom->id,
                    'game_unit_id' => $gameUnitId,
                    'amount'       => $unitData['amount']
                ]);
            } else {
                $newAmount = $unitData['amount'] + $foundUnit->amount;

                if ($newAmount > KingdomMaxValue::MAX_UNIT) {
                    $newAmount = KingdomMaxValue::MAX_UNIT;
                }

                $foundUnit->update(['amount' => $newAmount]);
            }
        }

        return $defendingKingdom->refresh();
    }
}
