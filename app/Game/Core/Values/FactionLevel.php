<?php

namespace App\Game\Core\Values;

class FactionLevel {

    const MAX_LEVEL = 4;

    const POINTS_NEEDED = [
        0 => 500,
        1 => 1000,
        2 => 2000,
        3 => 4000,
        4 => 8000,
    ];

    const GOLD_LEVEL = [
        1 => 500000,
        2 => 1000000,
        3 => 100000000,
        4 => 1000000000,
    ];

    public static function getPointsNeeded(int $currentLevel) {
        return self::POINTS_NEEDED[$currentLevel];
    }

    public static function isMaxLevel(int $currentLevel, int $currentPoints, bool $disabledAutoBattle = false): bool {
        $pointsNeeded = self::POINTS_NEEDED[self::MAX_LEVEL];

        if ($disabledAutoBattle) {
            $pointsNeeded = $pointsNeeded / 10;
        }

        return $currentLevel === self::MAX_LEVEL || $currentPoints === $pointsNeeded;
    }

    public static function gatPointsPerLevel(int $currentLevel): int {
        return $currentLevel > 0 ? 2 : 1;
    }

    public static function getGoldReward(int $currentLevel): int {
        return self::GOLD_LEVEL[$currentLevel];
    }
}