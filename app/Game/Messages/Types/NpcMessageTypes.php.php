<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum NpcMessageTypes: string implements BaseMessageType
{
    case SEER_ACTIONS = 'seer_actions';


    public function getValue(): string
    {
        return $this->value;
    }
}
