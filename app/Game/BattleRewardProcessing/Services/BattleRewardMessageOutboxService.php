<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestMessage;
use App\Flare\Models\User;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

class BattleRewardMessageOutboxService
{
    use SafelyBroadcastsEvents;

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

    public function emitUnemittedMessages(CharacterBattleRewardRequest $request): int
    {
        $emittedCount = 0;

        CharacterBattleRewardRequestMessage::query()
            ->where('character_battle_reward_request_id', $request->id)
            ->orderBy('id')
            ->chunkById(50, function ($messages) use (&$emittedCount): void {
                foreach ($messages as $message) {
                    if (! is_null($message->emitted_at)) {
                        $this->log('message.skipped_emitted', $message);

                        continue;
                    }

                    $dispatched = false;

                    try {
                        $user = User::find($message->user_id);

                        if (! is_null($user)) {
                            event(new ServerMessageEvent(
                                $user,
                                $message->message,
                                $message->message_id,
                                $message->source,
                                $message->item_id,
                                $message->link_text,
                            ));
                        }

                        $dispatched = true;
                    } catch (Throwable $throwable) {
                        Log::channel('reward_ledger')->warning('message.emit_failed', [
                            'character_id' => $message->character_id,
                            'request_id' => $message->character_battle_reward_request_id,
                            'step_name' => $message->step_name?->value,
                            'exception_class' => $throwable::class,
                            'exception_message' => $throwable->getMessage(),
                        ]);
                    }

                    if (! $dispatched) {
                        continue;
                    }

                    $message->update(['emitted_at' => now()]);
                    $emittedCount++;
                    $this->log('message.emitted', $message->refresh());
                }
            });

        return $emittedCount;
    }

    public function markEmitted(CharacterBattleRewardRequestMessage $message): void
    {
        if (! is_null($message->emitted_at)) {
            $this->log('message.skipped_emitted', $message);

            return;
        }

        $message->update(['emitted_at' => now()]);
        $this->log('message.emitted', $message->refresh());
    }

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
