<?php

namespace App\Game\BattleRewardProcessing\Enums;

enum BattleRewardRequestPriority: string
{
    case FIRST = 'first';
    case SECOND = 'second';
    case THIRD = 'third';

    public function order(): int
    {
        return match ($this) {
            self::FIRST => 1,
            self::SECOND => 2,
            self::THIRD => 3,
        };
    }
}
