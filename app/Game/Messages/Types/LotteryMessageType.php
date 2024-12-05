<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum LotteryMessageType: string implements BaseMessageType
{
    case LOTTO_MAX = 'lotto_max';
    case DAILY_LOTTERY = 'daily_lottery';

    public function getValue(): string
    {
        return $this->value;
    }
}
