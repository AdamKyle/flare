<?php

namespace App\Game\Messages\Types;

use App\Game\Messages\Types\Concerns\BaseMessageType;

enum ChatMessageTypes: string implements BaseMessageType
{
    case INVALID_MESSAGE_LENGTH = 'invalid_message_length';
    case CHATTING_TO_MUCH = 'chatting_to_much';

    public function getValue(): string
    {
        return $this->value;
    }
}
