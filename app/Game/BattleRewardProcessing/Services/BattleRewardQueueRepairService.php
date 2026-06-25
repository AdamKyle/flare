<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Events\BattleRewardQueueUpdated;
use App\Game\BattleRewardProcessing\Jobs\ProcessCharacterBattleRewardQueue;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BattleRewardQueueRepairService
{
    use SafelyBroadcastsEvents;

    public const FAILED_REASON = 'Queue processor heartbeat became stale and was repaired by admin repair. Reward was not retried automatically.';

    public function __construct(
        private readonly BattleRewardProcessingQueueManager $queueManager,
        private readonly MonitoredBugReportService $monitoredBugReportService,
    ) {}

    public function repairStaleQueues(): array
    {
        $summary = [
            'repaired_queue_state_count' => 0,
            'failed_processing_request_count' => 0,
            'restarted_processor_count' => 0,
            'cleared_inactive_queue_state_count' => 0,
        ];

        $stateIds = CharacterBattleRewardQueueState::query()
            ->stale($this->queueManager->staleCutoff())
            ->pluck('id');

        foreach ($stateIds as $stateId) {
            $result = DB::transaction(function () use ($stateId): ?array {
                $state = CharacterBattleRewardQueueState::query()
                    ->lockForUpdate()
                    ->find($stateId);

                if (is_null($state) || ! $this->queueManager->isQueueStateStale($state)) {
                    Log::channel('reward_processing')->debug('Stale repair skipped: state no longer stale or missing.', [
                        'queue_state_id' => $stateId,
                    ]);

                    return null;
                }

                if ($this->queueManager->isProcessorLocked($state->character_id)) {
                    Log::channel('reward_processing')->debug('Stale repair skipped: processor lock still held.', [
                        'character_id' => $state->character_id,
                        'queue_state_id' => $state->id,
                        'heartbeat_at' => $state->heartbeat_at?->toIso8601String(),
                    ]);

                    return null;
                }

                Log::channel('reward_processing')->warning('Stale repair detected: no live lock, heartbeat stale.', [
                    'character_id' => $state->character_id,
                    'queue_state_id' => $state->id,
                    'heartbeat_at' => $state->heartbeat_at?->toIso8601String(),
                ]);

                $failedCount = CharacterBattleRewardRequest::forCharacter($state->character_id)
                    ->processing()
                    ->update([
                        'status' => BattleRewardRequestStatus::FAILED,
                        'failed_reason' => self::FAILED_REASON,
                        'completed_at' => now(),
                    ]);

                Log::channel('reward_processing')->warning('Stale repair failed orphaned processing rows.', [
                    'character_id' => $state->character_id,
                    'queue_state_id' => $state->id,
                    'failed_count' => $failedCount,
                ]);

                if ($failedCount > 0) {
                    $this->monitoredBugReportService->reportError(
                        'battle-reward-queue-repair',
                        'Stale reward queue repaired: ' . $failedCount . ' processing request(s) marked failed.',
                        ['character_id' => $state->character_id, 'queue_state_id' => $state->id, 'failed_count' => $failedCount],
                        null,
                        $state->character_id,
                        (string) $state->id,
                    );
                }

                $hasPendingRequests = CharacterBattleRewardRequest::forCharacter($state->character_id)
                    ->pending()
                    ->exists();

                if ($hasPendingRequests) {
                    $state->update([
                        'is_processing' => true,
                        'started_at' => now(),
                        'heartbeat_at' => now(),
                    ]);

                    DB::afterCommit(function () use ($state): void {
                        Log::channel('reward_processing')->info('Stale repair restarted processor for pending rows.', [
                            'character_id' => $state->character_id,
                            'queue_state_id' => $state->id,
                        ]);

                        ProcessCharacterBattleRewardQueue::dispatch($state->character_id)
                            ->onConnection('battle_reward_processing')
                            ->onQueue('battle_reward_processing');
                    });
                } else {
                    $state->update([
                        'is_processing' => false,
                        'started_at' => null,
                        'heartbeat_at' => null,
                    ]);

                    Log::channel('reward_processing')->info('Stale repair cleared inactive queue state: no pending rows.', [
                        'character_id' => $state->character_id,
                        'queue_state_id' => $state->id,
                    ]);
                }

                DB::afterCommit(function () use ($state): void {
                    $this->safelyDispatchBroadcastEvent(
                        new BattleRewardQueueUpdated(
                            $state->character_id,
                            BattleRewardQueueUpdated::REPAIRED,
                        ),
                    );
                });

                return [
                    'failed_count' => $failedCount,
                    'restarted' => $hasPendingRequests,
                ];
            });

            if (is_null($result)) {
                continue;
            }

            $summary['repaired_queue_state_count']++;
            $summary['failed_processing_request_count'] += $result['failed_count'];

            if ($result['restarted']) {
                $summary['restarted_processor_count']++;
            } else {
                $summary['cleared_inactive_queue_state_count']++;
            }
        }

        return $summary;
    }
}
