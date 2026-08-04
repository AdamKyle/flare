<?php

namespace App\Game\Core\Chance;

use InvalidArgumentException;

class ChanceCalculator
{
    private const BASIS_POINTS_PER_PERCENT = 100;

    private const MAXIMUM_BASIS_POINTS = 10_000;

    public function __construct(private readonly RandomNumberGenerator $randomNumberGenerator) {}

    public function passesPercentage(float $basePercentage, float $modifierPercentagePoints = 0.0): bool
    {
        $percentage = max(0.0, min(100.0, $basePercentage + $modifierPercentagePoints));
        $threshold = (int) round($percentage * self::BASIS_POINTS_PER_PERCENT);

        if ($threshold === 0) {
            return false;
        }

        if ($threshold === self::MAXIMUM_BASIS_POINTS) {
            return true;
        }

        return $this->randomNumberGenerator->numberBetween(1, self::MAXIMUM_BASIS_POINTS) <= $threshold;
    }

    public function passesOneIn(int $denominator): bool
    {
        if ($denominator < 1) {
            throw new InvalidArgumentException('The chance denominator must be at least one.');
        }

        if ($denominator === 1) {
            return true;
        }

        return $this->randomNumberGenerator->numberBetween(1, $denominator) === 1;
    }
}
