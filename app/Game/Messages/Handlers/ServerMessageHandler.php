<?php

namespace App\Game\Messages\Handlers;

use App\Flare\Models\User;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\Concerns\BaseMessageType;

class ServerMessageHandler
{

    /**
     * @param ServerMessageBuilder $serverMessageBuilder
     */
    public function __construct(private ServerMessageBuilder $serverMessageBuilder) {}

    /**
     * Handle sending a message with additional information
     *
     * - Can pass in a formessage and a newValue, both are used in the string
     *
     * @param User $user
     * @param BaseMessageType $type
     * @param string|integer|null|null $forMessage
     * @param string|integer|null|null $newValue
     * @return void
     */
    public function handleMessageWithNewValue(User $user, BaseMessageType $type, string|int|null $forMessage = null, string|int|null $newValue = null): void
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation($type, $forMessage, $newValue);

        event(new ServerMessageEvent($user, $message));
    }

    /**
     * Handle sending a message with basic information.
     *
     * - Can pass in an id of an item to create a link
     *
     * @param User $user
     * @param BaseMessageType $type
     * @param string|integer|null|null $forMessage
     * @param integer|null $id
     * @return void
     */
    public function handleMessage(User $user, BaseMessageType $type, string|int|null $forMessage = null, ?int $id = null): void
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation($type, $forMessage);

        event(new ServerMessageEvent($user, $message, $id));
    }

    /**
     * Send a basic message
     *
     * @param User $user
     * @param string $message
     * @return void
     */
    public function sendBasicMessage(User $user, string $message): void
    {
        event(new ServerMessageEvent($user, $message));
    }
}
