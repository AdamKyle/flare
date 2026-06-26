<?php

namespace App\Game\Messages\Handlers;

use App\Flare\Models\User;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageContext;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageOutboxService;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\Concerns\BaseMessageType;

class ServerMessageHandler
{
    use SafelyBroadcastsEvents;

    /**
     * @param ServerMessageBuilder $serverMessageBuilder
     */
    public function __construct(
        private ServerMessageBuilder $serverMessageBuilder,
        private readonly BattleRewardMessageContext $battleRewardMessageContext,
        private readonly BattleRewardMessageOutboxService $battleRewardMessageOutboxService,
    ) {}

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

        $this->dispatchOrOutbox($user, $message);
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

        $this->dispatchOrOutbox($user, $message, $id);
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
        $this->dispatchOrOutbox($user, $message);
    }

    public function sendBasicMessageWithId(User $user, string $message, ?int $id = null): void
    {
        $this->dispatchOrOutbox($user, $message, $id);
    }

    private function dispatchOrOutbox(
        User $user,
        string $message,
        ?int $id = null,
        ?string $source = null,
        ?int $itemId = null,
        ?string $linkText = null,
    ): void {
        if (! $this->battleRewardMessageContext->active()) {
            $this->safelyDispatchBroadcastEvent(
                new ServerMessageEvent($user, $message, $id, $source, $itemId, $linkText),
                ['user_id' => $user->id],
            );

            return;
        }

        $storedMessage = $this->battleRewardMessageOutboxService->storeMessage(
            $this->battleRewardMessageContext->requestId(),
            $this->battleRewardMessageContext->characterId(),
            $user->id,
            $this->battleRewardMessageContext->stepName()?->value,
            $message,
            $id,
            $source,
            $itemId,
            $linkText,
        );

        $this->safelyDispatchBroadcastEvent(
            new ServerMessageEvent($user, $message, $id, $source, $itemId, $linkText),
            ['user_id' => $user->id],
        );

        $this->battleRewardMessageOutboxService->markEmitted($storedMessage);
    }
}
