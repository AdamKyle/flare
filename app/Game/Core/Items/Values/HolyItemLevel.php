<?php

namespace App\Game\Core\Items\Values;

enum HolyItemLevel: int
{
    case LEVEL_ONE = 1;
    case LEVEL_TWO = 2;
    case LEVEL_THREE = 3;
    case LEVEL_FOUR = 4;
    case LEVEL_FIVE = 5;

    public function maximumBonus(): int
    {
        return match ($this) {
            self::LEVEL_ONE => 3,
            self::LEVEL_TWO => 5,
            self::LEVEL_THREE => 8,
            self::LEVEL_FOUR => 10,
            self::LEVEL_FIVE => 15,
        };
    }
}
