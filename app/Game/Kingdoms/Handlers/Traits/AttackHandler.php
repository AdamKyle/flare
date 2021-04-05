<?php

namespace App\Game\Kingdoms\Handlers\Traits;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;

trait AttackHandler {

    public function calculatePerentageLost(int $totalAttack, int $totalDefence, bool $flipped = false): float {
        if (!$flipped) {
            return ($totalAttack / $totalDefence);
        }

        return  ($totalDefence / $totalAttack);
    }

    public function updateKingdomBuilding(KingdomBuilding $building, float $durabilityPercentageLost) {
        $durability = ceil($building->current_durability - ($building->current_durability * $durabilityPercentageLost));

        $building->update([
            'current_durability' => $durability < 0 ? 0 : $durability,
        ]);
    }

    public function healDefendingUnits(Kingdom $defender, $amount) {
        $totalUnitTypes = $defender->units->count();
        $totalHealed    = ($amount / $totalUnitTypes);

        foreach ($defender->units as $unit) {
            if (!$unit->gameUnit->can_not_be_healed) {
                $newAmount = $unit->amount * ($totalHealed > 1 ? $totalHealed : (1 + $totalHealed));

                $unit->update([
                    'amount' => $newAmount
                ]);
            }
        }
    }

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
}
