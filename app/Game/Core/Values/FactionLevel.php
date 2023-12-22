<?php

namespace App\Game\Core\Values;

class FactionLevel {

    const MAX_LEVEL = 5;

    const POINTS_NEEDED = [
        0 => 500,
        1 => 1000,
        2 => 2000,
        3 => 4000,
        4 => 8000,
        5 => 0,
    ];

    const GOLD_LEVEL = [
        1 => 500000,
        2 => 1000000,
        3 => 100000000,
        4 => 1000000000,
        5 => 5000000000,
    ];

    public static function getPointsNeeded(int $currentLevel) {
        return self::POINTS_NEEDED[$currentLevel];
    }

    public static function isMaxLevel(int $currentLevel): bool {
        return $currentLevel === self::MAX_LEVEL;
    }

    public static function gatPointsPerLevel(int $currentLevel): int {
        return $currentLevel > 0 ? 75 : 50;
    }

    public static function getGoldReward(int $currentLevel): int {
        return self::GOLD_LEVEL[$currentLevel];
    }
}
