<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestMessage;
use App\Flare\Models\User;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use App\Game\Messages\Events\ServerMessageEvent;
use Closure;
use Illuminate\Support\Facades\Log;
use Throwable;

class BattleRewardMessageOutboxService
{
    use SafelyBroadcastsEvents;

    /**
     * Store one durable, unemitted reward message for the request.
     *
     * @param int $requestId
     * @param int $characterId
     * @param int $userId
     * @param ?string $stepName
     * @param string $message
     * @param ?int $messageId
     * @param ?string $source
     * @param ?int $itemId
     * @param ?string $linkText
     * @return CharacterBattleRewardRequestMessage
     */
    public function storeMessage(
        int $requestId,
        int $characterId,
        int $userId,
        ?string $stepName,
        string $message,
        ?int $messageId = null,
        ?string $source = null,
        ?int $itemId = null,
        ?string $linkText = null,
    ): CharacterBattleRewardRequestMessage {
        $storedMessage = CharacterBattleRewardRequestMessage::query()->create([
            'character_battle_reward_request_id' => $requestId,
            'character_id' => $characterId,
            'user_id' => $userId,
            'step_name' => $stepName,
            'message' => $message,
            'message_id' => $messageId,
            'source' => $source,
            'item_id' => $itemId,
            'link_text' => $linkText,
        ]);

        $this->log('message.stored', $storedMessage);

        return $storedMessage;
    }

    /**
     * Bulk store an ordered list of durable, unemitted reward messages for the request, preserving their order.
     *
     * @param int $requestId
     * @param int $characterId
     * @param int $userId
     * @param ?string $stepName
     * @param array $messages
     * @return void
     */
    public function storeMessages(
        int $requestId,
        int $characterId,
        int $userId,
        ?string $stepName,
        array $messages,
    ): void {
        if ($messages === []) {
            return;
        }

        $now = now();

        $rows = array_map(fn (array $message): array => [
            'character_battle_reward_request_id' => $requestId,
            'character_id' => $characterId,
            'user_id' => $userId,
            'step_name' => $stepName,
            'message' => $message['message'],
            'message_id' => $message['message_id'] ?? null,
            'source' => $message['source'] ?? null,
            'item_id' => $message['item_id'] ?? null,
            'link_text' => $message['link_text'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $messages);

        // Chunked so a legitimate multi-thousand level stream stays under MySQL's prepared-statement placeholder limit.
        foreach (array_chunk($rows, 500) as $rowChunk) {
            CharacterBattleRewardRequestMessage::query()->insert($rowChunk);
        }

        Log::channel('reward_ledger')->debug('messages.stored', array_filter([
            'character_id' => $characterId,
            'request_id' => $requestId,
            'step_name' => $stepName,
            'stored_message_count' => count($rows),
        ], fn ($value): bool => ! is_null($value)));
    }

    /**
     * Emit every unemitted message for the request in id order, invoking the optional callback immediately before each message is dispatched.
     *
     * @param CharacterBattleRewardRequest $request
     * @param ?Closure $beforeEmit
     * @return int
     */
    public function emitUnemittedMessages(CharacterBattleRewardRequest $request, ?Closure $beforeEmit = null): int
    {
        $emittedCount = 0;
        $firstFailure = null;

        CharacterBattleRewardRequestMessage::query()
            ->where('character_battle_reward_request_id', $request->id)
            ->orderBy('id')
            ->chunkById(50, function ($messages) use (&$emittedCount, &$firstFailure, $beforeEmit): void {
                foreach ($messages as $message) {
                    if (! is_null($message->emitted_at)) {
                        $this->log('message.skipped_emitted', $message);

                        continue;
                    }

                    $failure = $this->emitMessage($message, $beforeEmit);

                    if (! is_null($failure)) {
                        $firstFailure ??= $failure;

                        continue;
                    }

                    $emittedCount++;
                }
            });

        if ($firstFailure instanceof Throwable) {
            throw $firstFailure;
        }

        return $emittedCount;
    }

    /**
     * Mark a stored message emitted when it has not been emitted already.
     *
     * @param CharacterBattleRewardRequestMessage $message
     * @return void
     */
    public function markEmitted(CharacterBattleRewardRequestMessage $message): void
    {
        if (! is_null($message->emitted_at)) {
            $this->log('message.skipped_emitted', $message);

            return;
        }

        $message->update(['emitted_at' => now()]);
        $this->log('message.emitted', $message->refresh());
    }

    /**
     * Dispatch one unemitted message and mark it emitted, returning the failure when it could not be dispatched.
     *
     * @param CharacterBattleRewardRequestMessage $message
     * @param ?Closure $beforeEmit
     * @return ?Throwable
     */
    private function emitMessage(CharacterBattleRewardRequestMessage $message, ?Closure $beforeEmit): ?Throwable
    {
        try {
            $this->dispatchMessage($message, $beforeEmit);
        } catch (Throwable $throwable) {
            Log::channel('reward_ledger')->warning('message.emit_failed', [
                'character_id' => $message->character_id,
                'request_id' => $message->character_battle_reward_request_id,
                'message_record_id' => $message->id,
                'message' => $message->message,
                'step_name' => $message->step_name?->value,
                'event_class' => ServerMessageEvent::class,
                'exception_class' => $throwable::class,
                'exception_message' => $throwable->getMessage(),
            ]);

            return $throwable;
        }

        $message->update(['emitted_at' => now()]);
        $this->log('message.emitted', $message->refresh());

        return null;
    }

    /**
     * Broadcast the stored message to its user, invoking the optional callback immediately before the broadcast.
     *
     * @param CharacterBattleRewardRequestMessage $message
     * @param ?Closure $beforeEmit
     * @return void
     */
    private function dispatchMessage(CharacterBattleRewardRequestMessage $message, ?Closure $beforeEmit): void
    {
        $user = User::find($message->user_id);

        if (is_null($user)) {
            return;
        }

        if (! is_null($beforeEmit)) {
            $beforeEmit($message);
        }

        event(new ServerMessageEvent(
            $user,
            $message->message,
            $message->message_id,
            $message->source,
            $message->item_id,
            $message->link_text,
        ));
    }

    /**
     * Write one structured reward-message ledger diagnostic log entry.
     *
     * @param string $event
     * @param CharacterBattleRewardRequestMessage $message
     * @return void
     */
    private function log(string $event, CharacterBattleRewardRequestMessage $message): void
    {
        Log::channel('reward_ledger')->debug($event, array_filter([
            'character_id' => $message->character_id,
            'request_id' => $message->character_battle_reward_request_id,
            'step_name' => $message->step_name?->value,
            'status' => is_null($message->emitted_at) ? 'pending' : 'emitted',
        ], fn ($value): bool => ! is_null($value)));
    }
}
