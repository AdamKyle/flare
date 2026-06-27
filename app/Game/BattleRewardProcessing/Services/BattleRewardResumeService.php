<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Events\BattleRewardQueueUpdated;
use App\Game\BattleRewardProcessing\Jobs\ProcessCharacterBattleRewardQueue;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BattleRewardResumeService
{
    use SafelyBroadcastsEvents;

    public const LEGACY_FAILED_REASON = 'Legacy pre-ledger processing request recovered after stale heartbeat and no durable reward ledger.';

    public function __construct(
        private readonly BattleRewardProcessingQueueManager $queueManager,
        private readonly BattleRewardLedgerService $battleRewardLedgerService,
    ) {}

    public function resumeInterrupted(bool $apply = true, ?array $stateIds = null): array
    {
        $summary = [
            'repaired_queue_state_count' => 0,
            'resumed_processing_request_count' => 0,
            'legacy_failed_processing_request_count' => 0,
            'restarted_processor_count' => 0,
            'cleared_inactive_queue_state_count' => 0,
            'resumable_step_count' => 0,
            'unemitted_message_count' => 0,
            'would_resume_processing_request_count' => 0,
            'would_legacy_fail_processing_request_count' => 0,
        ];

        $query = CharacterBattleRewardQueueState::query()->stale($this->queueManager->staleCutoff());

        if (! is_null($stateIds)) {
            $query->whereIn('id', $stateIds);
        }

        foreach ($query->pluck('id') as $stateId) {
            $result = DB::transaction(function () use ($stateId, $apply): ?array {
                $state = CharacterBattleRewardQueueState::query()
                    ->lockForUpdate()
                    ->find($stateId);

                if (is_null($state) || ! $this->queueManager->isQueueStateStale($state)) {
                    return null;
                }

                if ($this->queueManager->isProcessorLocked($state->character_id)) {
                    return null;
                }

                $processingRequests = CharacterBattleRewardRequest::query()
                    ->with(['steps', 'messages'])
                    ->forCharacter($state->character_id)
                    ->processing()
                    ->lockForUpdate()
                    ->get();

                $legacyFailedCount = 0;
                $resumedCount = 0;
                $resumableStepCount = 0;

                foreach ($processingRequests as $request) {
                    if ($request->steps->isEmpty()) {
                        $legacyFailedCount++;

                        if ($apply) {
                            $request->update([
                                'status' => BattleRewardRequestStatus::FAILED,
                                'failed_reason' => self::LEGACY_FAILED_REASON,
                                'completed_at' => now(),
                            ]);
                        }

                        continue;
                    }

                    $resumedCount++;

                    if ($apply) {
                        foreach ($request->steps as $step) {
                            if ($this->battleRewardLedgerService->markStaleStepResumable($step, $this->queueManager->staleCutoff())) {
                                $resumableStepCount++;
                            }
                        }

                        $request->update([
                            'status' => BattleRewardRequestStatus::RESUMABLE,
                            'failed_reason' => null,
                            'completed_at' => null,
                        ]);

                        $this->battleRewardLedgerService->log('resume.recovered_request', $request->refresh(), null, [
                            'status' => BattleRewardRequestStatus::RESUMABLE->value,
                        ]);
                    }
                }

                $hasQueuedRequests = CharacterBattleRewardRequest::query()
                    ->forCharacter($state->character_id)
                    ->queued()
                    ->exists();

                $shouldRestart = $hasQueuedRequests || $resumedCount > 0;

                if ($apply) {
                    $state->update([
                        'is_processing' => $shouldRestart,
                        'started_at' => $shouldRestart ? now() : null,
                        'heartbeat_at' => $shouldRestart ? now() : null,
                    ]);

                    DB::afterCommit(function () use ($state, $shouldRestart): void {
                        if ($shouldRestart) {
                            ProcessCharacterBattleRewardQueue::dispatch($state->character_id)
                                ->onConnection('battle_reward_processing')
                                ->onQueue('battle_reward_processing');

                            $this->battleRewardLedgerService->log('resume.dispatched_processor', new CharacterBattleRewardRequest([
                                'character_id' => $state->character_id,
                            ]));
                        }

                        $this->safelyDispatchBroadcastEvent(
                            new BattleRewardQueueUpdated($state->character_id, BattleRewardQueueUpdated::REPAIRED),
                        );
                    });
                }

                return [
                    'resumed_count' => $resumedCount,
                    'legacy_failed_count' => $legacyFailedCount,
                    'resumable_step_count' => $resumableStepCount,
                    'restarted' => $shouldRestart,
                    'cleared' => ! $shouldRestart,
                    'unemitted_count' => CharacterBattleRewardRequest::query()
                        ->forCharacter($state->character_id)
                        ->whereHas('messages', fn ($query) => $query->unemitted())
                        ->withCount(['messages as unemitted_messages_count' => fn ($query) => $query->unemitted()])
                        ->get()
                        ->sum('unemitted_messages_count'),
                ];
            });

            if (is_null($result)) {
                continue;
            }

            $summary['repaired_queue_state_count']++;
            $summary[$apply ? 'resumed_processing_request_count' : 'would_resume_processing_request_count'] += $result['resumed_count'];
            $summary[$apply ? 'legacy_failed_processing_request_count' : 'would_legacy_fail_processing_request_count'] += $result['legacy_failed_count'];
            $summary['resumable_step_count'] += $result['resumable_step_count'];
            $summary['unemitted_message_count'] += $result['unemitted_count'];

            if ($result['restarted']) {
                $summary['restarted_processor_count']++;
            }

            if ($result['cleared']) {
                $summary['cleared_inactive_queue_state_count']++;
            }
        }

        Log::channel('reward_ledger')->debug('resume.completed', $summary);

        return $summary;
    }

    public function resumeAll(bool $apply, ?int $characterId = null): array
    {
        $summary = [
            'recovered_processing_request_count' => 0,
            'would_recover_processing_request_count' => 0,
            'pending_only_lane_wake_count' => 0,
            'would_pending_only_lane_wake_count' => 0,
            'inactive_queue_state_count' => 0,
            'would_inactive_queue_state_count' => 0,
            'legacy_failed_processing_request_count' => 0,
            'legacy_skipped_processing_request_count' => 0,
            'locked_skipped_count' => 0,
            'locked_recovery_blocked_count' => 0,
            'released_lock_count' => 0,
            'would_release_lock_count' => 0,
            'restarted_processor_count' => 0,
            'resumable_step_count' => 0,
            'unemitted_message_count' => 0,
        ];

        $fromQueueStates = CharacterBattleRewardQueueState::query()
            ->where('is_processing', true)
            ->when(! is_null($characterId), fn ($q) => $q->where('character_id', $characterId))
            ->pluck('character_id')
            ->all();

        $fromRequests = CharacterBattleRewardRequest::query()
            ->whereIn('status', [BattleRewardRequestStatus::PROCESSING, BattleRewardRequestStatus::RESUMABLE])
            ->whereHas('steps')
            ->when(! is_null($characterId), fn ($q) => $q->where('character_id', $characterId))
            ->distinct()
            ->pluck('character_id')
            ->diff($fromQueueStates)
            ->all();

        $allCharacterIds = array_merge($fromQueueStates, $fromRequests);

        foreach ($allCharacterIds as $cid) {
            $isLocked = $this->queueManager->isProcessorLocked($cid);

            if ($isLocked) {
                $hasLedgerBackedProcessingRow = CharacterBattleRewardRequest::query()
                    ->forCharacter($cid)
                    ->processing()
                    ->whereHas('steps')
                    ->exists();

                if ($hasLedgerBackedProcessingRow) {
                    if ($apply) {
                        $this->queueManager->forceReleaseProcessorLock($cid);
                        $summary['released_lock_count']++;
                    } else {
                        $summary['would_release_lock_count']++;

                        continue;
                    }
                } else {
                    $summary['locked_recovery_blocked_count']++;

                    continue;
                }
            }

            $result = DB::transaction(function () use ($cid, $apply): ?array {
                $state = CharacterBattleRewardQueueState::query()
                    ->where('character_id', $cid)
                    ->lockForUpdate()
                    ->first();

                if (is_null($state) && $apply) {
                    CharacterBattleRewardQueueState::query()->insertOrIgnore([
                        'character_id' => $cid,
                        'is_processing' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $state = CharacterBattleRewardQueueState::query()
                        ->where('character_id', $cid)
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                $isStale = ! is_null($state) && $this->queueManager->isQueueStateStale($state);

                $processingRequests = CharacterBattleRewardRequest::query()
                    ->with(['steps', 'messages'])
                    ->forCharacter($cid)
                    ->processing()
                    ->lockForUpdate()
                    ->get();

                $recoveredCount = 0;
                $legacyFailedCount = 0;
                $legacySkippedCount = 0;
                $resumableStepCount = 0;
                $isPendingOnly = false;
                $shouldMarkInactive = false;
                $shouldDispatch = false;

                if ($processingRequests->isNotEmpty()) {
                    foreach ($processingRequests as $request) {
                        if ($request->steps->isNotEmpty()) {
                            if ($apply) {
                                foreach ($request->steps as $step) {
                                    if (in_array($step->status, [
                                        BattleRewardStepStatus::RUNNING,
                                        BattleRewardStepStatus::CHECKPOINTED,
                                    ], true)) {
                                        $step->update([
                                            'status' => BattleRewardStepStatus::RESUMABLE,
                                            'heartbeat_at' => now(),
                                        ]);
                                        $resumableStepCount++;
                                    }
                                }

                                $request->update([
                                    'status' => BattleRewardRequestStatus::RESUMABLE,
                                    'failed_reason' => null,
                                    'completed_at' => null,
                                ]);

                                $this->battleRewardLedgerService->log('resume.recovered_request', $request->refresh(), null, [
                                    'status' => BattleRewardRequestStatus::RESUMABLE->value,
                                ]);
                            }

                            $recoveredCount++;
                        } elseif ($isStale) {
                            $legacyFailedCount++;

                            if ($apply) {
                                $request->update([
                                    'status' => BattleRewardRequestStatus::FAILED,
                                    'failed_reason' => self::LEGACY_FAILED_REASON,
                                    'completed_at' => now(),
                                ]);
                            }
                        } else {
                            $legacySkippedCount++;
                        }
                    }

                    if ($recoveredCount > 0) {
                        $shouldDispatch = true;
                    }
                } else {
                    $hasQueuedRequests = $this->queueManager->hasPendingRequests($cid);

                    if ($hasQueuedRequests) {
                        $isPendingOnly = true;
                        $shouldDispatch = true;
                    } else {
                        $shouldMarkInactive = true;
                    }
                }

                if ($apply) {
                    if ($shouldMarkInactive) {
                        $state?->update([
                            'is_processing' => false,
                            'started_at' => null,
                            'heartbeat_at' => null,
                        ]);
                    } elseif ($shouldDispatch) {
                        $state?->update([
                            'is_processing' => true,
                            'started_at' => now(),
                            'heartbeat_at' => now(),
                        ]);
                    }

                    DB::afterCommit(function () use ($cid, $shouldDispatch, $shouldMarkInactive): void {
                        if ($shouldDispatch) {
                            ProcessCharacterBattleRewardQueue::dispatch($cid)
                                ->onConnection('battle_reward_processing')
                                ->onQueue('battle_reward_processing');

                            $this->battleRewardLedgerService->log('resume.dispatched_processor', new CharacterBattleRewardRequest([
                                'character_id' => $cid,
                            ]));
                        }

                        if (! $shouldMarkInactive) {
                            $this->safelyDispatchBroadcastEvent(
                                new BattleRewardQueueUpdated($cid, BattleRewardQueueUpdated::REPAIRED),
                            );
                        }
                    });
                }

                $unemittedCount = CharacterBattleRewardRequest::query()
                    ->forCharacter($cid)
                    ->whereHas('messages', fn ($q) => $q->unemitted())
                    ->withCount(['messages as unemitted_count' => fn ($q) => $q->unemitted()])
                    ->get()
                    ->sum('unemitted_count');

                return [
                    'recovered_count' => $recoveredCount,
                    'legacy_failed_count' => $legacyFailedCount,
                    'legacy_skipped_count' => $legacySkippedCount,
                    'resumable_step_count' => $resumableStepCount,
                    'is_pending_only' => $isPendingOnly,
                    'should_mark_inactive' => $shouldMarkInactive,
                    'should_dispatch' => $shouldDispatch,
                    'unemitted_count' => $unemittedCount,
                ];
            });

            if (is_null($result)) {
                continue;
            }

            $summary[$apply ? 'recovered_processing_request_count' : 'would_recover_processing_request_count'] += $result['recovered_count'];
            $summary['legacy_failed_processing_request_count'] += $result['legacy_failed_count'];
            $summary['legacy_skipped_processing_request_count'] += $result['legacy_skipped_count'];
            $summary['resumable_step_count'] += $result['resumable_step_count'];
            $summary['unemitted_message_count'] += $result['unemitted_count'];

            if ($result['is_pending_only']) {
                $summary[$apply ? 'pending_only_lane_wake_count' : 'would_pending_only_lane_wake_count']++;
            }

            if ($result['should_mark_inactive']) {
                $summary[$apply ? 'inactive_queue_state_count' : 'would_inactive_queue_state_count']++;
            }

            if ($apply && $result['should_dispatch']) {
                $summary['restarted_processor_count']++;
            }
        }

        Log::channel('reward_ledger')->debug('resume.all_completed', $summary);

        return $summary;
    }

    public function forceResumeInterrupted(bool $apply, ?int $characterId): array
    {
        $summary = [
            'force_recovered_request_count' => 0,
            'would_force_recover_request_count' => 0,
            'resumable_step_count' => 0,
            'unemitted_message_count' => 0,
            'restarted_processor_count' => 0,
        ];

        $stateQuery = CharacterBattleRewardQueueState::query()
            ->where('is_processing', true);

        if (! is_null($characterId)) {
            $stateQuery->where('character_id', $characterId);
        }

        foreach ($stateQuery->pluck('character_id') as $cid) {
            if ($this->queueManager->isProcessorLocked($cid)) {
                Log::channel('reward_ledger')->debug('resume.force_skip_locked', ['character_id' => $cid]);

                continue;
            }

            $processingRequests = CharacterBattleRewardRequest::query()
                ->with(['steps', 'messages'])
                ->forCharacter($cid)
                ->processing()
                ->get();

            $ledgerBacked = $processingRequests->filter(fn ($r) => $r->steps->isNotEmpty());

            if ($ledgerBacked->isEmpty()) {
                continue;
            }

            $resumableStepCount = 0;
            $restarted = false;

            foreach ($ledgerBacked as $request) {
                if ($apply) {
                    DB::transaction(function () use ($request, &$resumableStepCount): void {
                        $locked = CharacterBattleRewardRequest::query()
                            ->with('steps')
                            ->lockForUpdate()
                            ->findOrFail($request->id);

                        if ($locked->status !== BattleRewardRequestStatus::PROCESSING) {
                            return;
                        }

                        foreach ($locked->steps as $step) {
                            if (in_array($step->status, [
                                BattleRewardStepStatus::RUNNING,
                                BattleRewardStepStatus::CHECKPOINTED,
                            ], true)) {
                                $step->update([
                                    'status' => BattleRewardStepStatus::RESUMABLE,
                                    'heartbeat_at' => now(),
                                ]);
                                $resumableStepCount++;
                            }
                        }

                        $locked->update([
                            'status' => BattleRewardRequestStatus::RESUMABLE,
                            'failed_reason' => null,
                            'completed_at' => null,
                        ]);

                        $this->battleRewardLedgerService->log('resume.force_recovered_request', $locked->refresh(), null, [
                            'status' => BattleRewardRequestStatus::RESUMABLE->value,
                        ]);
                    });

                    $summary['force_recovered_request_count']++;
                } else {
                    $summary['would_force_recover_request_count']++;
                    $resumableStepCount += $request->steps
                        ->filter(fn ($step) => in_array($step->status, [
                            BattleRewardStepStatus::RUNNING,
                            BattleRewardStepStatus::CHECKPOINTED,
                        ], true))
                        ->count();
                }

                $summary['resumable_step_count'] += $resumableStepCount;
                $resumableStepCount = 0;
            }

            if ($apply && $summary['force_recovered_request_count'] > 0) {
                $state = CharacterBattleRewardQueueState::query()
                    ->where('character_id', $cid)
                    ->first();

                if (! is_null($state)) {
                    $state->update(['is_processing' => true, 'started_at' => now(), 'heartbeat_at' => now()]);
                }

                ProcessCharacterBattleRewardQueue::dispatch($cid)
                    ->onConnection('battle_reward_processing')
                    ->onQueue('battle_reward_processing');

                $this->safelyDispatchBroadcastEvent(
                    new BattleRewardQueueUpdated($cid, BattleRewardQueueUpdated::REPAIRED),
                );

                $restarted = true;
            }

            if ($restarted) {
                $summary['restarted_processor_count']++;
            }

            $summary['unemitted_message_count'] += CharacterBattleRewardRequest::query()
                ->forCharacter($cid)
                ->whereHas('messages', fn ($q) => $q->unemitted())
                ->withCount(['messages as unemitted_count' => fn ($q) => $q->unemitted()])
                ->get()
                ->sum('unemitted_count');
        }

        Log::channel('reward_ledger')->debug('resume.force_completed', $summary);

        return $summary;
    }
}
