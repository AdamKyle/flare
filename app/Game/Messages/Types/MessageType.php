<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum MessageType: string implements BaseMessageType
{
    case GOLD = 'gold';
    case GOLD_RUSH = 'gold_rush';


    public function getValue(): string
    {
        return $this->value;
    }
}
