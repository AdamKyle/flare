<?php

namespace App\Game\Messages\Handlers;

use App\Flare\Models\User;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;

class ServerMessageHandler
{
    private ServerMessageBuilder $serverMessageBuilder;

    public function __construct(ServerMessageBuilder $serverMessageBuilder)
    {
        $this->serverMessageBuilder = $serverMessageBuilder;
    }

    /**
     * handle the server message based on type and additional info passed in.
     */
    public function handleMessage(User $user, string $type, string|int|null $forMessage = null, ?int $id = null): void
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation($type, $forMessage);

        broadcast(new ServerMessageEvent($user, $message, $id));
    }

    /**
     * Send a basic message.
     */
    public function sendBasicMessage(User $user, string $message): void
    {
        broadcast(new ServerMessageEvent($user, $message));
    }
}
