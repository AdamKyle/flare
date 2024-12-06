<?php

namespace App\Game\Messages\Factories;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Game\Messages\Types\ChatMessageTypes;
use App\Game\Messages\Types\MapMessageTypes;
use InvalidArgumentException;

class AssignMessageType
{

    /**
     * Assigne the string type to an enum
     *
     * - Enums are: ChatMessageTypes and MapMessageTypes
     *
     * @param string $messageType
     * @throws InvalidArgumentException
     * @return void
     */
    public function assignType(string $messageType): void
    {
        $messageTypeClasses = [ChatMessageTypes::class, MapMessageTypes::class];

        foreach ($messageTypeClasses as $messageTypeClass) {
            try {
                $messageTypeClass::from($messageType);

                return;
            } catch (Exception $e) {
                continue;
            }
        }

        Log::error('Unknown message type to assign [AssignMessageType@assignType] for message: ' . $messageType . '.');

        throw new InvalidArgumentException('Unknown message type for: ' . $messageType);
    }
}
