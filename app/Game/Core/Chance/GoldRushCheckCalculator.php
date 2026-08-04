<?php

namespace App\Game\Core\Chance;

class GoldRushCheckCalculator
{
    public function __construct(private readonly ChanceCalculator $chanceCalculator) {}

    public function fetchGoldRushChance(float $gameMapBonus = 0.0, float $locationBonus = 0.0): bool
    {
        $chance = max(0.0, min(1.0, 0.01 + $gameMapBonus + $locationBonus));

        return $this->chanceCalculator->passesPercentage($chance * 100);
    }
}
