<?php

namespace App\Game\Messages\Services;

use App\Flare\Handlers\MessageThrottledHandler;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;

class ServerMessage
{
    private MessageThrottledHandler $messageThrottledHandler;

    private ServerMessageBuilder $serverMessageBuilder;

    public function __construct(MessageThrottledHandler $messageThrottledHandler, ServerMessageBuilder $serverMessage)
    {
        $this->messageThrottledHandler = $messageThrottledHandler;
        $this->serverMessageBuilder = $serverMessage;
    }

    /**
     * Generates a server message for a specific type.
     *
     * - If the type is chatting_to_much, we handle this through the MessageThrottledHandler
     *
     * @see MessageThrottledHandler
     */
    public function generateServerMessage(string $type): void
    {
        if ($type === 'chatting_to_much') {
            $this->messageThrottledHandler->forUser(auth()->user())->increaseThrottleCount()->silence();

            return;
        }

        event(new ServerMessageEvent(auth()->user(), $this->serverMessageBuilder->build($type)));
    }

    /**
     * Generates a server message for a custom message.
     */
    public function generateServerMessageForCustomMessage(string $customMessage): void
    {
        event(new ServerMessageEvent(auth()->user(), $customMessage));
    }
}
