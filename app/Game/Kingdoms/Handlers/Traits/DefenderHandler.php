<?php

namespace App\Game\Kingdoms\Handlers\Traits;

use App\Game\Kingdoms\Values\KingdomMaxValue;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;

trait DefenderHandler {

    /**
     * Has the kingdom building fallen?
     *
     * @param KingdomBuilding $building
     * @return bool
     */
    public function hasKingdomBuildingFallen(KingdomBuilding $building): bool {
        return $building->current_durability === 0;
    }

    /**
     * Updates the kingdom buildings with the new durability.
     *
     * @param Collection $buildings
     * @param float $percentageOfDurabilityLost
     * @return void
     */
    public function updateAllKingdomBuildings(Collection $buildings, float $percentageOfDurabilityLost): void {
        $buildingsStillStanding = $buildings->where('current_durability', '!=', 0)->all();

        if (empty($buildingsStillStanding)) {
            return;
        }

        $percentageLost = ($percentageOfDurabilityLost / count($buildingsStillStanding));

        foreach ($buildingsStillStanding as $building) {
            $newDurability = $building->current_durability - ($building->current_durability * $percentageLost);

            $building->update([
                'current_durability' => $newDurability > 0 ? $newDurability : 0,
            ]);
        }
    }

    /**
     * Update defender units with new values.
     *
     * @param Kingdom $defender
     * @param float $percentageOfUnitsLost
     * @return void
     */
    public function updateDefenderUnits(Kingdom $defender, float $percentageOfUnitsLost): void {
        $oldAmount = [];

        foreach ($defender->units as $unit) {
            $oldAmount[$unit->id] = $unit->amount;

            $newAmount = $this->getNewUnitTotal($unit->amount, $percentageOfUnitsLost);

            $unit->update([
                'amount' => $newAmount > 0 ? $newAmount : 0,
            ]);
        }

        $defender = $defender->refresh();

        $this->healDefendingUnits($defender, $oldAmount, $this->getHealingAmountForDefender($defender));
    }

    /**
     * Heal the defending units.
     *
     * We make sure not to heal the amount of units to pas the old amounts values..
     *
     * @param Kingdom $defender
     * @param array $oldAmounts
     * @param $amount
     * @return void
     */
    public function healDefendingUnits(Kingdom $defender, array $oldAmounts, float $amount): void {
        $totalUnitTypes = $defender->units->count();
        $totalHealed    = ($amount / $totalUnitTypes);

        foreach ($defender->units as $unit) {
            if (!$unit->gameUnit->can_not_be_healed) {
                $newAmount = $unit->amount * ($totalHealed > 1 ? $totalHealed : (1 + $totalHealed));

                $oldAmount = $oldAmounts[$unit->id];

                if ($newAmount > $oldAmount) {
                    $newAmount = $oldAmount;
                }

                $unit->update([
                    'amount' => $newAmount
                ]);
            }
        }
    }

    /**
     * Get the healing amount for the defender's units.
     *
     * @param Kingdom $defender
     * @return float|int
     */
    public function getHealingAmountForDefender(Kingdom $defender) {
        $healingUnits  = $defender->units()->join('game_units', function($join) {
            $join->on('kingdom_units.game_unit_id', 'game_units.id')->where('game_units.can_heal', true)->where('kingdom_units.amount', '>', '0');
        })->get();

        $healingAmount = 0.00;

        if ($healingUnits->isEmpty()) {
            return $healingAmount;
        }

        forEach($healingUnits as $healingUnit) {
            $healingAmount += ($healingUnit->heal_percentage * $healingUnit->amount);
        }

        return $healingAmount;
    }

    /**
     * Get the total defence including any bonuses.
     *
     * There is a bonus for the amount of defender units you have as opposed to regular units that can
     * or cannot attack.
     *
     * There is also a bonus if you have walls that haven't lost their durability.
     *
     * @param Kingdom $defender
     * @return float|int
     */
    public function getTotalDefenceBonus(Kingdom $defender) {
        $totalUnitTypes = $defender->units()->count();
        $totalDefenders = $defender->units()->join('game_units', function($join) {
            $join->on('kingdom_units.game_unit_id', 'game_units.id')->where('game_units.defender', true)->where('kingdom_units.amount', '>', 0);
        })->count();

        $walls          = $defender->buildings->where('is_walls', true)->where('current_durability', '>', 0)->first();
        $wallsBonus     = 0;

        if (!is_null($walls)) {
            if ($walls->current_durability > 0) {
                $wallsBonus = ($walls->level / 100);
            }
        }

        $treasuryBonus  = $defender->treasury / KingdomMaxValue::MAX_TREASURY;
        $treasuryBonus += $defender->fetchDefenceBonusFromPassive();

        return ($totalDefenders / $totalUnitTypes) + $wallsBonus + $treasuryBonus;
    }

    /**
     * Get the total attack of defenders siege units.
     *
     * @param Collection $siegeUnits
     * @return int
     */
    public function defenderSiegeUnitsAttack(Collection $siegeUnits): int {
        $totalAttack = 0;

        foreach ($siegeUnits as $siegeUnit) {
            $totalAttack += $siegeUnit->gameUnit->attack * $siegeUnit->amount;
        }

        return $totalAttack;
    }
}
