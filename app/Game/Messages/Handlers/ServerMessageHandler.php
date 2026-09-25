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
     * @param BattleRewardMessageContext $battleRewardMessageContext
     * @param BattleRewardMessageOutboxService $battleRewardMessageOutboxService
     */
    public function __construct(
        private ServerMessageBuilder $serverMessageBuilder,
        private readonly BattleRewardMessageContext $battleRewardMessageContext,
        private readonly BattleRewardMessageOutboxService $battleRewardMessageOutboxService,
    ) {}

    /**
     * Send a typed server message built from a gained amount and a new total.
     *
     * @param User $user
     * @param BaseMessageType $type
     * @param string|int|null $forMessage
     * @param string|int|null $newValue
     * @return void
     */
    public function handleMessageWithNewValue(User $user, BaseMessageType $type, string|int|null $forMessage = null, string|int|null $newValue = null): void
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation($type, $forMessage, $newValue);

        $this->dispatchOrOutbox($user, $message);
    }

    /**
     * Send a typed server message, optionally linked to an item by id.
     *
     * @param User $user
     * @param BaseMessageType $type
     * @param string|int|null $forMessage
     * @param ?int $id
     * @return void
     */
    public function handleMessage(User $user, BaseMessageType $type, string|int|null $forMessage = null, ?int $id = null): void
    {
        $message = $this->serverMessageBuilder->buildWithAdditionalInformation($type, $forMessage);

        $this->dispatchOrOutbox($user, $message, $id);
    }

    /**
     * Send a plain server message.
     *
     * @param User $user
     * @param string $message
     * @return void
     */
    public function sendBasicMessage(User $user, string $message): void
    {
        $this->dispatchOrOutbox($user, $message);
    }

    /**
     * Send a plain server message, optionally linked to an item by id.
     *
     * @param User $user
     * @param string $message
     * @param ?int $id
     * @return void
     */
    public function sendBasicMessageWithId(User $user, string $message, ?int $id = null): void
    {
        $this->dispatchOrOutbox($user, $message, $id);
    }

    /**
     * Send a plain server message with a clickable item link.
     *
     * @param User $user
     * @param string $message
     * @param int $id
     * @param ?string $source
     * @param ?string $linkText
     * @return void
     */
    public function sendBasicMessageWithLink(User $user, string $message, int $id, ?string $source, ?string $linkText): void
    {
        $this->dispatchOrOutbox($user, $message, $id, $source, null, $linkText);
    }

    /**
     * Broadcast the message immediately, or store it in the durable reward outbox while a reward request is being processed.
     *
     * @param User $user
     * @param string $message
     * @param ?int $id
     * @param ?string $source
     * @param ?int $itemId
     * @param ?string $linkText
     * @return void
     */
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

        $this->battleRewardMessageOutboxService->storeMessage(
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
    }
}
