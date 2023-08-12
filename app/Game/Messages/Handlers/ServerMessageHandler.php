<?php

namespace App\Game\Messages\Handlers;

use App\Flare\Models\User;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;

class ServerMessageHandler {

    /**
     * @var ServerMessageBuilder $serverMessageBuilder
     */
    private ServerMessageBuilder $serverMessageBuilder;

    /**
     * @param ServerMessageBuilder $serverMessageBuilder
     */
    public function __construct(ServerMessageBuilder $serverMessageBuilder) {
        $this->serverMessageBuilder = $serverMessageBuilder;
    }

    /**
     * handle the server message based on type and additional info passed in.
     *
     * @param User $user
     * @param string $type
     * @param string|int|null $forMessage
     * @param int|null $id
     * @return void
     */
    public function handleMessage(User $user, string $type, string|int $forMessage = null, int $id = null): void {
        $message =  $this->serverMessageBuilder->buildWithAdditionalInformation($type, $forMessage);
        dump($message);
        broadcast(new ServerMessageEvent($user, $message, $id));
    }

    /**
     * Send a basic message.
     *
     * @param User $user
     * @param string $message
     * @return void
     */
    public function sendBasicMessage(User $user, string $message): void {
        broadcast(new ServerMessageEvent($user, $message));
    }
}
