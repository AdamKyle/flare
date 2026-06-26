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
}
