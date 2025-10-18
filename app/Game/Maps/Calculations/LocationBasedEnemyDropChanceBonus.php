<?php

namespace App\Game\Maps\Calculations;

class LocationBasedEnemyDropChanceBonus
{
    /**
     * Calculate a bounded bonus drop-chance percentage from a location's enemy strength increase.
     * The curve begins at 5.00 when the strength increase is 0.00 and smoothly approaches 15.00 as the value grows.
     */
    public function calculateDropChanceBonusPercent(float $enemyStrengthIncrease): float
    {
        if ($enemyStrengthIncrease < 0.0) {
            $enemyStrengthIncrease = 0.0;
        }

        $minimumDropChancePercent = 5.0;
        $maximumDropChancePercent = 15.0;
        $dropChanceRangePercent = $maximumDropChancePercent - $minimumDropChancePercent;

        $progressBasedOnStrength = $enemyStrengthIncrease / (1.0 + $enemyStrengthIncrease);
        $unroundedBonusPercent = $minimumDropChancePercent + ($dropChanceRangePercent * $progressBasedOnStrength);

        return round($unroundedBonusPercent, 2);
    }
}
