<?php

namespace App\Game\Messages\Services;


use App\Flare\Handlers\MessageThrottledHandler;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;

class ServerMessage {

    /**
     * @var MessageThrottledHandler $messageThrottledHandler
     */
    private MessageThrottledHandler $messageThrottledHandler;

    /**
     * @var ServerMessageBuilder $serverMessageBuilder
     */
    private ServerMessageBuilder $serverMessageBuilder;

    /**
     * @param MessageThrottledHandler $messageThrottledHandler
     * @param ServerMessageBuilder $serverMessage
     */
    public function __construct(MessageThrottledHandler $messageThrottledHandler, ServerMessageBuilder $serverMessage) {
        $this->messageThrottledHandler = $messageThrottledHandler;
        $this->serverMessageBuilder    = $serverMessage;
    }

    /**
     * Generates a server message for a specific type.
     *
     * - If the type is chatting_to_much, we handle this through the MessageThrottledHandler
     *
     * @param string $type
     * @return void
     * @see MessageThrottledHandler
     */
    public function generateServerMessage(string $type): void {
        if ($type === 'chatting_to_much') {
            $this->messageThrottledHandler->forUser(auth()->user())->increaseThrottleCount()->silence();

            return;
        }

        event(new ServerMessageEvent(auth()->user(), $this->serverMessageBuilder->build($type)));
    }

    /**
     * Generates a server message for a custom message.
     *
     * @param string $customMessage
     * @return void
     */
    public function generateServerMessageForCustomMessage(string $customMessage): void {
        event(new ServerMessageEvent(auth()->user(), $customMessage));
    }

}
