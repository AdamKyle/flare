<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum MovementMessageTypes: string implements BaseMessageType
{
    case PLANE_TRANSFER = 'plane_transfer';
    case MOVE_LOCATION = 'moved_location';

    public function getValue(): string
    {
        return $this->value;
    }
}
