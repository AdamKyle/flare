<?php

namespace App\Flare\Calculators;

use Facades\App\Flare\RandomNumber\RandomNumberGenerator;

class GoldRushCheckCalculator
{
    public function fetchGoldRushChance(float $gameMapBonus = 0.0, float $locationBonus = 0.0): bool
    {
        $chance = max(0.0, min(1.0, 0.01 + $gameMapBonus + $locationBonus));
        $chanceBasisPoints = (int) round($chance * 10000);
        $roll = RandomNumberGenerator::generateTrueRandomNumber(10000);

        return $roll <= $chanceBasisPoints;
    }
}
