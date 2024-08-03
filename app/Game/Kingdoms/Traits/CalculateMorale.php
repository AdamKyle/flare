<?php

namespace App\Game\Kingdoms\Traits;

use App\Flare\Models\Kingdom;

trait CalculateMorale
{
    /**
     * Calculate new morale.
     *
     * - Skip buildings who do not increase or decrease morale.
     */
    public function calculateNewMorale(Kingdom $kingdom, float $morale): float
    {

        $buildings = $kingdom->buildings;

        foreach ($buildings as $building) {

            if (is_null($building->morale_increase) && is_null($building->morale_decrease)) {
                continue;
            }

            $currentDurability = $building->current_durability;
            $maxDurability = $building->max_durability;

            if ($currentDurability < $maxDurability) {
                $morale -= $building->morale_decrease;

            }

            if ($currentDurability === $maxDurability) {
                $morale += $building->morale_increase;
            }
        }

        if ($morale < 0) {
            return 0.0;
        }

        if ($morale > 1) {
            return 1.0;
        }

        return $morale;
    }
}
