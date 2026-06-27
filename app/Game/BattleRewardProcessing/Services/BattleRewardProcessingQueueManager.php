<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Events\BattleRewardQueueUpdated;
use App\Game\BattleRewardProcessing\Jobs\ProcessCharacterBattleRewardQueue;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BattleRewardProcessingQueueManager
{
    use SafelyBroadcastsEvents;

    private const STALE_AFTER_MINUTES = 5;

    /**
     * Must exceed the Horizon job timeout (300 s) plus any heavy XP path duration.
     * 1800 s ensures the lock outlives any normal processor run and does not
     * expire mid-execution under heavy exploration rewards. After a Horizon
     * restart, graceful shutdown releases the lock via finally; hard-kill lets
     * TTL expire. The --force flag on the resume command exists specifically to
     * recover the stuck state that follows a Horizon restart before TTL expires.
     */
    private const PROCESSOR_LOCK_SECONDS = 1800;

    private const ENQUEUE_CREATE_ATTEMPTS = 3;

    public const ORPHANED_PROCESSING_FAILED_REASON = 'Orphaned processing request recovered after stale heartbeat and no live processor lock.';

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

        Log::channel('reward_processing')->debug('Enqueue entered.', [
            'character_id' => $characterId,
            'priority' => $priority->value,
            'source_type' => $sourceType->value,
            'source_id' => $sourceId,
        ]);

        $request = null;
        $firstLockException = null;

        for ($attempt = 1; $attempt <= self::ENQUEUE_CREATE_ATTEMPTS; $attempt++) {
            try {
                $request = CharacterBattleRewardRequest::create([
                    'character_id' => $characterId,
                    'priority' => $priority,
                    'source_type' => $sourceType,
                    'source_id' => is_null($sourceId) ? null : (string) $sourceId,
                    'handler_payload' => $handlerPayload,
                    'status' => BattleRewardRequestStatus::PENDING,
                ]);

                break;
            } catch (QueryException $exception) {
                $exceptionCode = (int) $exception->getCode();
                $previousCode = (int) ($exception->getPrevious()?->getCode() ?? 0);
                $isRetryable = in_array($exceptionCode, [1205, 1213], true)
                    || in_array($previousCode, [1205, 1213], true)
                    || str_contains($exception->getMessage(), '1205')
                    || str_contains($exception->getMessage(), '1213')
                    || str_contains($exception->getMessage(), 'Lock wait timeout exceeded')
                    || str_contains($exception->getMessage(), 'Deadlock found');

                if (! $isRetryable) {
                    throw $exception;
                }

                $firstLockException ??= $exception;

                Log::channel('reward_processing')->warning('Reward request creation retry after database lock error.', [
                    'character_id' => $characterId,
                    'source_type' => $sourceType->value,
                    'source_id' => $sourceId,
                    'attempt' => $attempt,
                    'exception_code' => $exceptionCode !== 0 ? $exceptionCode : $previousCode,
                ]);

                if ($attempt === self::ENQUEUE_CREATE_ATTEMPTS) {
                    throw $firstLockException;
                }

                usleep(50_000);
            }
        }

        Log::channel('reward_processing')->debug('Enqueue created request.', [
            'character_id' => $characterId,
            'request_id' => $request->id,
            'status' => $request->status->value,
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

            $pendingCount = CharacterBattleRewardRequest::forCharacter($characterId)->pending()->count();
            $processingCount = CharacterBattleRewardRequest::forCharacter($characterId)->processing()->count();

            Log::channel('reward_processing')->debug('Enqueue sees queue state.', [
                'character_id' => $characterId,
                'queue_state_id' => $state->id,
                'is_processing' => $state->is_processing,
                'heartbeat_at' => $state->heartbeat_at?->toIso8601String(),
                'pending_count' => $pendingCount,
                'processing_count' => $processingCount,
            ]);

            if ($state->is_processing) {
                $isStale = $this->isQueueStateStale($state);
                $isLocked = $this->isProcessorLocked($characterId);

                if ($isLocked) {
                    Log::channel('reward_processing')->debug('Enqueue decides processor already running (lock held).', [
                        'character_id' => $characterId,
                        'is_stale' => $isStale,
                    ]);

                    return false;
                }

                $ledgerRecoveredCount = $this->recoverLedgerBackedProcessingRequests($characterId);
                $legacyRecoveredCount = $isStale ? $this->recoverOrphanedProcessingRequests($characterId) : 0;
                $shouldWake = $ledgerRecoveredCount > 0 || $isStale;

                if (! $shouldWake) {
                    Log::channel('reward_processing')->debug('Enqueue decides processor already running (fresh heartbeat, no ledger rows to recover).', [
                        'character_id' => $characterId,
                        'lock_available' => true,
                        'ledger_recovered' => $ledgerRecoveredCount,
                    ]);

                    return false;
                }

                Log::channel('reward_processing')->warning('Enqueue recovered interrupted processing state. Waking processor.', [
                    'character_id' => $characterId,
                    'queue_state_id' => $state->id,
                    'heartbeat_at' => $state->heartbeat_at?->toIso8601String(),
                    'processing_count' => $processingCount,
                    'ledger_recovered_count' => $ledgerRecoveredCount,
                    'legacy_recovered_count' => $legacyRecoveredCount,
                ]);

                $state->update([
                    'is_processing' => true,
                    'started_at' => now(),
                    'heartbeat_at' => now(),
                ]);

                DB::afterCommit(function () use ($characterId): void {
                    Log::channel('reward_processing')->info('Enqueue wakes processor after interrupted recovery.', [
                        'character_id' => $characterId,
                    ]);

                    ProcessCharacterBattleRewardQueue::dispatch($characterId)
                        ->onConnection('battle_reward_processing')
                        ->onQueue('battle_reward_processing');

                    $this->safelyDispatchBroadcastEvent(
                        new BattleRewardQueueUpdated($characterId, 'activated'),
                    );
                });

                return true;
            }

            if ($this->isProcessorLocked($characterId)) {
                Log::channel('reward_processing')->debug('Enqueue sees unlocked state but lock is held; marking processing.', [
                    'character_id' => $characterId,
                ]);

                $state->update([
                    'is_processing' => true,
                    'started_at' => now(),
                    'heartbeat_at' => now(),
                ]);

                return false;
            }

            $state->update([
                'is_processing' => true,
                'started_at' => now(),
                'heartbeat_at' => now(),
            ]);

            DB::afterCommit(function () use ($characterId): void {
                Log::channel('reward_processing')->info('Enqueue wakes processor.', [
                    'character_id' => $characterId,
                ]);

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

    public function recoverOrphanedProcessingRequests(int $characterId): int
    {
        $state = CharacterBattleRewardQueueState::where('character_id', $characterId)->first();

        if (is_null($state) || ! $this->isQueueStateStale($state)) {
            Log::channel('reward_processing')->debug('recoverOrphanedProcessingRequests: skipped — heartbeat is fresh or queue state is missing.', [
                'character_id' => $characterId,
                'heartbeat_at' => $state?->heartbeat_at?->toIso8601String(),
            ]);

            return 0;
        }

        $count = 0;

        CharacterBattleRewardRequest::query()
            ->with('steps')
            ->forCharacter($characterId)
            ->processing()
            ->get()
            ->each(function (CharacterBattleRewardRequest $request) use (&$count): void {
                if ($request->steps->isNotEmpty()) {
                    $request->steps()
                        ->whereIn('status', [
                            BattleRewardStepStatus::RUNNING,
                            BattleRewardStepStatus::CHECKPOINTED,
                        ])
                        ->update([
                            'status' => BattleRewardStepStatus::RESUMABLE,
                            'heartbeat_at' => now(),
                        ]);

                    $request->update([
                        'status' => BattleRewardRequestStatus::RESUMABLE,
                        'failed_reason' => null,
                        'completed_at' => null,
                    ]);

                    Log::channel('reward_ledger')->debug('resume.recovered_request', [
                        'character_id' => $request->character_id,
                        'request_id' => $request->id,
                        'status' => BattleRewardRequestStatus::RESUMABLE->value,
                        'source_type' => $request->source_type?->value,
                        'source_id' => $request->source_id,
                    ]);

                    $count++;

                    return;
                }

                $request->update([
                    'status' => BattleRewardRequestStatus::FAILED,
                    'failed_reason' => self::ORPHANED_PROCESSING_FAILED_REASON,
                    'completed_at' => now(),
                ]);

                $count++;
            });

        if ($count > 0) {
            $this->safelyDispatchBroadcastEvent(
                new BattleRewardQueueUpdated($characterId, 'failed'),
            );
        }

        Log::channel('reward_processing')->warning('recoverOrphanedProcessingRequests: stale heartbeat detected; recovered orphaned processing rows.', [
            'character_id' => $characterId,
            'heartbeat_at' => $state->heartbeat_at?->toIso8601String(),
            'recovered_count' => $count,
        ]);

        return $count;
    }

    public function recoverLedgerBackedProcessingRequests(int $characterId): int
    {
        $count = 0;

        CharacterBattleRewardRequest::query()
            ->with('steps')
            ->forCharacter($characterId)
            ->processing()
            ->get()
            ->each(function (CharacterBattleRewardRequest $request) use (&$count): void {
                if ($request->steps->isEmpty()) {
                    return;
                }

                $request->steps()
                    ->whereIn('status', [
                        BattleRewardStepStatus::RUNNING,
                        BattleRewardStepStatus::CHECKPOINTED,
                    ])
                    ->update([
                        'status' => BattleRewardStepStatus::RESUMABLE,
                        'heartbeat_at' => now(),
                    ]);

                $request->update([
                    'status' => BattleRewardRequestStatus::RESUMABLE,
                    'failed_reason' => null,
                    'completed_at' => null,
                ]);

                Log::channel('reward_ledger')->debug('resume.force_recovered_request', [
                    'character_id' => $request->character_id,
                    'request_id' => $request->id,
                    'status' => BattleRewardRequestStatus::RESUMABLE->value,
                    'source_type' => $request->source_type?->value,
                    'source_id' => $request->source_id,
                ]);

                $count++;
            });

        if ($count > 0) {
            $this->safelyDispatchBroadcastEvent(
                new BattleRewardQueueUpdated($characterId, 'repaired'),
            );
        }

        Log::channel('reward_processing')->info('recoverLedgerBackedProcessingRequests completed.', [
            'character_id' => $characterId,
            'recovered_count' => $count,
        ]);

        return $count;
    }

    public function nextRequest(int $characterId): ?CharacterBattleRewardRequest
    {
        return DB::transaction(function () use ($characterId): ?CharacterBattleRewardRequest {
            $request = CharacterBattleRewardRequest::forCharacter($characterId)
                ->resumable()
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (is_null($request)) {
                $request = CharacterBattleRewardRequest::forCharacter($characterId)
                    ->pending()
                    ->orderedForProcessing()
                    ->lockForUpdate()
                    ->first();
            }

            if (is_null($request)) {
                Log::channel('reward_processing')->debug('Next request returns null: no pending or resumable rows.', [
                    'character_id' => $characterId,
                ]);

                return null;
            }

            if ($this->hasProcessingRequests($characterId)) {
                Log::channel('reward_processing')->debug('Next request returns null: active processing row exists.', [
                    'character_id' => $characterId,
                    'pending_request_id' => $request->id,
                ]);

                return null;
            }

            Log::channel('reward_processing')->debug('Next request claim attempt.', [
                'character_id' => $characterId,
                'request_id' => $request->id,
                'priority' => $request->priority?->value,
                'source_type' => $request->source_type?->value,
            ]);

            return $this->markProcessing($request);
        });
    }

    public function markProcessing(
        CharacterBattleRewardRequest $request,
    ): CharacterBattleRewardRequest {
        if (! in_array($request->status, [BattleRewardRequestStatus::PENDING, BattleRewardRequestStatus::RESUMABLE], true)) {
            return $request->refresh();
        }

        $request->update([
            'status' => BattleRewardRequestStatus::PROCESSING,
            'failed_reason' => null,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $this->updateHeartbeat($request->character_id);

        Log::channel('reward_processing')->debug('Request marked processing.', [
            'character_id' => $request->character_id,
            'request_id' => $request->id,
            'source_type' => $request->source_type?->value,
            'priority' => $request->priority?->value,
            'started_at' => now()->toIso8601String(),
        ]);

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

        Log::channel('reward_processing')->debug('Request marked completed.', [
            'character_id' => $request->character_id,
            'request_id' => $request->id,
            'completed_at' => now()->toIso8601String(),
        ]);

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

        Log::channel('reward_processing')->warning('Request marked failed.', [
            'character_id' => $request->character_id,
            'request_id' => $request->id,
            'failed_reason' => $failedReason,
            'completed_at' => now()->toIso8601String(),
        ]);

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
                ->whereIn('status', [
                    BattleRewardRequestStatus::PENDING,
                    BattleRewardRequestStatus::RESUMABLE,
                ])
                ->exists();

            if ($hasPendingRequests) {
                $state->update(['heartbeat_at' => now()]);

                Log::channel('reward_processing')->debug('markQueueInactiveIfEmpty: pending rows remain, kept active.', [
                    'character_id' => $characterId,
                ]);

                return false;
            }

            if ($this->hasProcessingRequests($characterId)) {
                $state->update(['heartbeat_at' => now()]);

                Log::channel('reward_processing')->debug('markQueueInactiveIfEmpty: processing row still active, kept active.', [
                    'character_id' => $characterId,
                ]);

                return true;
            }

            $state->update([
                'is_processing' => false,
                'started_at' => null,
                'heartbeat_at' => null,
            ]);

            Log::channel('reward_processing')->info('Processor marked inactive: queue is empty.', [
                'character_id' => $characterId,
                'queue_state_id' => $state->id,
            ]);

            DB::afterCommit(fn () => $this->safelyDispatchBroadcastEvent(
                new BattleRewardQueueUpdated($characterId, 'deactivated'),
            ));

            return true;
        });
    }

    public function hasPendingRequests(int $characterId): bool
    {
        return CharacterBattleRewardRequest::forCharacter($characterId)
            ->whereIn('status', [
                BattleRewardRequestStatus::PENDING,
                BattleRewardRequestStatus::RESUMABLE,
            ])
            ->exists();
    }

    public function hasProcessingRequests(int $characterId): bool
    {
        return CharacterBattleRewardRequest::forCharacter($characterId)->processing()->exists();
    }

    public function processorLock(int $characterId): Lock
    {
        return Cache::lock($this->processorLockKey($characterId), self::PROCESSOR_LOCK_SECONDS);
    }

    public function isProcessorLocked(int $characterId): bool
    {
        $lock = $this->processorLock($characterId);

        if (! $lock->get()) {
            return true;
        }

        $lock->release();

        return false;
    }

    public function forceReleaseProcessorLock(int $characterId): void
    {
        $this->processorLock($characterId)->forceRelease();
    }

    private function processorLockKey(int $characterId): string
    {
        return 'character-reward-queue:' . $characterId;
    }
}
