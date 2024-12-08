<?php

namespace App\Game\Messages\Factories;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Game\Messages\Types\ChatMessageTypes;
use App\Game\Messages\Types\Concerns\BaseMessageType;
use App\Game\Messages\Types\MapMessageTypes;
use InvalidArgumentException;
use ValueError;

class AssignMessageType
{

    /**
     * Assigne the string type to an enum
     *
     * - Enums are: ChatMessageTypes and MapMessageTypes
     *
     * @param string $messageType
     * @throws InvalidArgumentException
     * @return BaseMessageType
     */
    public function assignType(string $messageType): BaseMessageType
    {
        $messageTypeClasses = [ChatMessageTypes::class, MapMessageTypes::class];

        foreach ($messageTypeClasses as $messageTypeClass) {
            try {
                return $messageTypeClass::from($messageType);
            } catch (ValueError $e) {
                continue;
            }
        }

        Log::error('Unknown message type to assign [AssignMessageType@assignType] for message: ' . $messageType . '.');

        throw new InvalidArgumentException('Unknown message type for: ' . $messageType);
    }
}
