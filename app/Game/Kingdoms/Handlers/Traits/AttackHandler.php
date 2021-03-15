<?php

namespace App\Game\Kingdoms\Handlers\Traits;

use App\Flare\Models\Building;

trait AttackHandler {

    public function calculatePerentageLost(int $totalAttack, int $totalDefence, bool $flipped = false): float {
        if (!$flipped) {
            return ($totalAttack / $totalDefence);
        }

        return  ($totalDefence / $totalAttack);
    }

    public function updateBuilding(Building $building, float $durabilityPercentageLost) {
        $durability = ceil($building->current_durability - ($building->current_durability * $durabilityPercentageLost));

        $building->update([
            'current_durability' => $durability < 0 ? 0 : $durability,
        ]);
    }
}