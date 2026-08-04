<?php

namespace App\Game\Core\Items\Services;

use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Items\Values\HolyItemLevel;

class HolyItemBonusGenerator
{
    public function __construct(private readonly RandomNumberGenerator $randomNumberGenerator) {}

    public function getRandomStatIncrease(HolyItemLevel $level): int
    {
        return $this->randomNumberGenerator->numberBetween(1, $level->maximumBonus());
    }

    public function getRandomDevoidanceIncrease(HolyItemLevel $level): float
    {
        return $this->randomNumberGenerator->numberBetween(1, $level->maximumBonus()) / 1000;
    }
}
