<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum MapMessageTypes: string implements BaseMessageType
{
    case CANT_MOVE = 'cant_move';
    case CANNOT_MOVE_RIGHT = 'cannot_move_right';
    case CANNOT_MOVE_LEFT = 'cannot_move_left';
    case CANNOT_MOVE_DOWN = 'cannot_move_down';
    case CANNOT_MOVE_UP = 'cannot_move_up';

    public function getValue(): string
    {
        return $this->value;
    }
}
