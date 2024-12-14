<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum AdminMessageTypes: string implements BaseMessageType
{
    case DELETED_AFFIX = 'deleted_affix';

    public function getValue(): string
    {
        return $this->value;
    }
}
