<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Events\BattleRewardQueueUpdated;
use App\Game\BattleRewardProcessing\Jobs\ProcessCharacterBattleRewardQueue;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class BattleRewardProcessingQueueManager
{
    use SafelyBroadcastsEvents;

    private const STALE_AFTER_MINUTES = 5;

    public function staleCutoff(): Carbon
    {
        return now()->subMinutes(self::STALE_AFTER_MINUTES);
    }

    public function isQueueStateStale(CharacterBattleRewardQueueState $state): bool
    {
        return $state->is_processing
            && (is_null($state->heartbeat_at)
                || $state->heartbeat_at->lte($this->staleCutoff()));
    }

    public function enqueue(
        Character|int $character,
        BattleRewardRequestPriority $priority,
        BattleRewardRequestSourceType $sourceType,
        int|string|null $sourceId,
        array $handlerPayload,
    ): CharacterBattleRewardRequest {
        $characterId = $character instanceof Character ? $character->id : $character;

        $request = CharacterBattleRewardRequest::create([
            'character_id' => $characterId,
            'priority' => $priority,
            'source_type' => $sourceType,
            'source_id' => is_null($sourceId) ? null : (string) $sourceId,
            'handler_payload' => $handlerPayload,
            'status' => BattleRewardRequestStatus::PENDING,
        ]);

        $this->safelyDispatchBroadcastEvent(
            new BattleRewardQueueUpdated($characterId, 'created'),
        );

        $this->ensureProcessorRunning($characterId);

        return $request;
    }

    public function ensureProcessorRunning(Character|int $character): bool
    {
        $characterId = $character instanceof Character ? $character->id : $character;

        return DB::transaction(function () use ($characterId): bool {
            CharacterBattleRewardQueueState::query()->insertOrIgnore([
                'character_id' => $characterId,
                'is_processing' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $state = CharacterBattleRewardQueueState::where('character_id', $characterId)
                ->lockForUpdate()
                ->firstOrFail();

            $isStale = $this->isQueueStateStale($state);

            if ($state->is_processing && ! $isStale) {
                return false;
            }

            if ($isStale) {
                CharacterBattleRewardRequest::forCharacter($characterId)
                    ->processing()
                    ->update([
                        'status' => BattleRewardRequestStatus::FAILED,
                        'failed_reason' => 'Processor heartbeat became stale before the request completed.',
                        'completed_at' => now(),
                    ]);
            }

            $state->update([
                'is_processing' => true,
                'started_at' => now(),
                'heartbeat_at' => now(),
            ]);

            DB::afterCommit(function () use ($characterId): void {
                ProcessCharacterBattleRewardQueue::dispatch($characterId)
                    ->onConnection('battle_reward_processing')
                    ->onQueue('battle_reward_processing');
                $this->safelyDispatchBroadcastEvent(
                    new BattleRewardQueueUpdated($characterId, 'activated'),
                );
            });

            return true;
        });
    }

    public function nextRequest(int $characterId): ?CharacterBattleRewardRequest
    {
        return DB::transaction(function () use ($characterId): ?CharacterBattleRewardRequest {
            $request = CharacterBattleRewardRequest::forCharacter($characterId)
                ->pending()
                ->orderedForProcessing()
                ->lockForUpdate()
                ->first();

            if (is_null($request)) {
                return null;
            }

            return $this->markProcessing($request);
        });
    }

    public function markProcessing(
        CharacterBattleRewardRequest $request,
    ): CharacterBattleRewardRequest {
        $request->update([
            'status' => BattleRewardRequestStatus::PROCESSING,
            'failed_reason' => null,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $this->updateHeartbeat($request->character_id);
        $this->safelyDispatchBroadcastEvent(
            new BattleRewardQueueUpdated($request->character_id, 'processing'),
        );

        return $request->refresh();
    }

    public function markCompleted(CharacterBattleRewardRequest $request): void
    {
        $request->update([
            'status' => BattleRewardRequestStatus::COMPLETED,
            'failed_reason' => null,
            'completed_at' => now(),
        ]);

        $this->updateHeartbeat($request->character_id);
        $this->safelyDispatchBroadcastEvent(
            new BattleRewardQueueUpdated($request->character_id, 'completed'),
        );
    }

    public function markFailed(CharacterBattleRewardRequest $request, Throwable|string $reason): void
    {
        $failedReason = $reason instanceof Throwable
            ? $reason::class . ': ' . $reason->getMessage()
            : $reason;

        $request->update([
            'status' => BattleRewardRequestStatus::FAILED,
            'failed_reason' => $failedReason,
            'completed_at' => now(),
        ]);

        $this->updateHeartbeat($request->character_id);
        $this->safelyDispatchBroadcastEvent(
            new BattleRewardQueueUpdated($request->character_id, 'failed'),
        );
    }

    public function updateHeartbeat(int $characterId): void
    {
        CharacterBattleRewardQueueState::where('character_id', $characterId)->update([
            'heartbeat_at' => now(),
        ]);
    }

    public function markQueueInactiveIfEmpty(int $characterId): bool
    {
        return DB::transaction(function () use ($characterId): bool {
            $state = CharacterBattleRewardQueueState::where('character_id', $characterId)
                ->lockForUpdate()
                ->first();

            if (is_null($state)) {
                return true;
            }

            $hasPendingRequests = CharacterBattleRewardRequest::forCharacter($characterId)
                ->pending()
                ->exists();

            if ($hasPendingRequests) {
                $state->update(['heartbeat_at' => now()]);

                return false;
            }

            $state->update([
                'is_processing' => false,
                'started_at' => null,
                'heartbeat_at' => null,
            ]);

            DB::afterCommit(fn () => $this->safelyDispatchBroadcastEvent(
                new BattleRewardQueueUpdated($characterId, 'deactivated'),
            ));

            return true;
        });
    }

    public function hasPendingRequests(int $characterId): bool
    {
        return CharacterBattleRewardRequest::forCharacter($characterId)->pending()->exists();
    }
}
