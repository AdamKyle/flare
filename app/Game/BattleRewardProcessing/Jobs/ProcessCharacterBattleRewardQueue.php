<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\Monster;
use App\Flare\Models\Quest;
use App\Game\Automation\Delve\Events\DelveStatusUpdated;
use App\Game\Automation\Exploration\Services\ExplorationLogService;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Exceptions\WeeklyRewardInventoryFullException;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardLiveUpdateService;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageOutboxService;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\BattleRewardProcessing\Services\BattleRewardSharedContextService;
use App\Game\BattleRewardProcessing\Services\BattleRewardStepPlanService;
use App\Game\BattleRewardProcessing\Values\BattleRewardSharedContext;
use App\Game\Character\Exceptions\MissingInventoryException;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Quests\Handlers\NpcQuestRewardHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProcessCharacterBattleRewardQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SafelyBroadcastsEvents, SerializesModels;

    private const MAX_REQUESTS = 50;

    private const MAX_SECONDS = 20;

    public int $timeout = 300;

    /**
     * @param int $characterId
     */
    public function __construct(private readonly int $characterId) {}

    /**
     * Process the Character's pending battle reward requests under the per-Character processor lock.
     *
     * @param BattleRewardProcessingQueueManager $queueManager
     * @param BattleRewardService $battleRewardService
     * @param NpcQuestRewardHandler $npcQuestRewardHandler
     * @param GuideQuestService $guideQuestService
     * @param ExplorationLogService $explorationLogService
     * @param BattleRewardStepPlanService $battleRewardStepPlanService
     * @param BattleRewardSharedContextService $battleRewardSharedContextService
     * @param BattleRewardLiveUpdateService $battleRewardLiveUpdateService
     * @param ?BattleRewardLedgerService $battleRewardLedgerService
     * @param ?BattleRewardMessageOutboxService $battleRewardMessageOutboxService
     * @return void
     */
    public function handle(
        BattleRewardProcessingQueueManager $queueManager,
        BattleRewardService $battleRewardService,
        NpcQuestRewardHandler $npcQuestRewardHandler,
        GuideQuestService $guideQuestService,
        ExplorationLogService $explorationLogService,
        BattleRewardStepPlanService $battleRewardStepPlanService,
        BattleRewardSharedContextService $battleRewardSharedContextService,
        BattleRewardLiveUpdateService $battleRewardLiveUpdateService,
        ?BattleRewardLedgerService $battleRewardLedgerService = null,
        ?BattleRewardMessageOutboxService $battleRewardMessageOutboxService = null,
    ): void {
        $battleRewardLedgerService ??= new BattleRewardLedgerService();
        $battleRewardMessageOutboxService ??= new BattleRewardMessageOutboxService();

        $lockKey = 'character-reward-queue:'.$this->characterId;

        Log::channel('reward_processing')->debug('Processor job starts. Attempting lock.', [
            'character_id' => $this->characterId,
            'lock_key' => $lockKey,
            'job_attempt' => $this->attempts(),
            'memory_usage' => memory_get_usage(true),
        ]);

        $processorLock = $queueManager->processorLock($this->characterId);

        if (! $processorLock->get()) {
            Log::channel('reward_processing')->debug('Processor lock denied. Another processor is active.', [
                'character_id' => $this->characterId,
                'lock_key' => $lockKey,
                'lock_acquired' => false,
            ]);

            return;
        }

        Log::channel('reward_processing')->info('Processor lock acquired.', [
            'character_id' => $this->characterId,
            'lock_key' => $lockKey,
            'lock_acquired' => true,
        ]);

        $ledgerRecoveredCount = $queueManager->recoverLedgerBackedProcessingRequests($this->characterId);
        $orphanRecoveredCount = $queueManager->recoverOrphanedProcessingRequests($this->characterId);

        if ($ledgerRecoveredCount > 0 || $orphanRecoveredCount > 0) {
            Log::channel('reward_processing')->warning('Processor recovered interrupted or orphaned rows at start.', [
                'character_id' => $this->characterId,
                'ledger_recovered' => $ledgerRecoveredCount,
                'orphan_recovered' => $orphanRecoveredCount,
            ]);
        }

        if ($queueManager->hasProcessingRequests($this->characterId)) {
            Log::channel('reward_processing')->info('Fresh legacy processing rows remain after recovery. Exiting.', [
                'character_id' => $this->characterId,
            ]);

            $processorLock->release();

            return;
        }

        $startedAt = microtime(true);
        $loopStartedAtNs = hrtime(true);
        $processed = 0;
        $shouldDispatchAfterUnlock = false;
        $shouldCheckPendingAfterUnlock = false;
        $capacityRetryPaused = false;
        $secondaryPlayerUpdatesDirty = false;
        $heartbeatCallback = fn () => $queueManager->updateHeartbeat($this->characterId);

        Log::channel('reward_processing')->debug('Processor loop starts.', [
            'character_id' => $this->characterId,
            'pending_count' => CharacterBattleRewardRequest::forCharacter($this->characterId)->pending()->count(),
        ]);

        try {
            while ($processed < self::MAX_REQUESTS && microtime(true) - $startedAt < self::MAX_SECONDS) {
                Log::channel('reward_processing')->debug('Next request claim attempt.', [
                    'character_id' => $this->characterId,
                    'processed_so_far' => $processed,
                    'elapsed_ms' => intdiv(hrtime(true) - $loopStartedAtNs, 1_000_000),
                ]);

                $request = $queueManager->nextRequest($this->characterId);

                if (is_null($request)) {
                    Log::channel('reward_processing')->debug('Next request returned null. Ending loop.', [
                        'character_id' => $this->characterId,
                        'processed_so_far' => $processed,
                    ]);

                    break;
                }

                Log::channel('reward_processing')->debug('Before reward processing.', [
                    'character_id' => $this->characterId,
                    'request_id' => $request->id,
                    'source_type' => $request->source_type?->value,
                    'priority' => $request->priority?->value,
                    'status' => $request->status?->value,
                ]);

                $notificationRetryScheduled = false;
                $requestStartedAtNs = hrtime(true);
                $requestResult = 'failed';

                try {
                    $payload = $request->handler_payload;

                    $sharedContext = null;

                    if (in_array($request->source_type, [
                        BattleRewardRequestSourceType::BATTLE,
                        BattleRewardRequestSourceType::EXPLORATION,
                        BattleRewardRequestSourceType::AUTOMATION,
                    ], true)) {
                        $character = Character::find($request->character_id);
                        $monster = Monster::find($payload['monster_id']);

                        if (is_null($character) || is_null($monster)) {
                            throw new RuntimeException(
                                'Unable to build battle reward context for request '.$request->id.' because the Character or Monster no longer exists.',
                            );
                        }

                        $sharedContext = $battleRewardSharedContextService->build(
                            $request,
                            $character,
                            $monster,
                        );
                    }

                    $stepPlan = match ($request->source_type) {
                        BattleRewardRequestSourceType::BATTLE,
                        BattleRewardRequestSourceType::EXPLORATION,
                        BattleRewardRequestSourceType::AUTOMATION => $battleRewardStepPlanService->planBattleLike(
                            $request,
                            $sharedContext,
                        ),
                        BattleRewardRequestSourceType::FACTION_LOYALTY => $battleRewardStepPlanService->planFactionLoyalty(),
                        BattleRewardRequestSourceType::QUEST,
                        BattleRewardRequestSourceType::RAID_QUEST,
                        BattleRewardRequestSourceType::GUIDE_QUEST => $battleRewardStepPlanService->planQuest(),
                        BattleRewardRequestSourceType::FUTURE => throw new RuntimeException(
                            'No ledger step plan exists for future reward requests.',
                        ),
                    };

                    $battleRewardLedgerService->ensureSteps(
                        $request,
                        $stepPlan,
                    );

                    match ($request->source_type) {
                        BattleRewardRequestSourceType::BATTLE,
                        BattleRewardRequestSourceType::EXPLORATION,
                        BattleRewardRequestSourceType::AUTOMATION,
                        BattleRewardRequestSourceType::FACTION_LOYALTY => $this->processBattleRewardRequest(
                            $battleRewardService,
                            $request,
                            $sharedContext,
                            $heartbeatCallback,
                        ),
                        BattleRewardRequestSourceType::QUEST,
                        BattleRewardRequestSourceType::RAID_QUEST => $this->processQuestReward(
                            $npcQuestRewardHandler,
                            $payload['quest_id'],
                        ),
                        BattleRewardRequestSourceType::GUIDE_QUEST => $guideQuestService
                            ->processQueuedRewards(
                                Character::findOrFail($this->characterId),
                                GuideQuest::findOrFail($payload['guide_quest_id']),
                            ),
                        BattleRewardRequestSourceType::FUTURE => throw new RuntimeException(
                            'No reward processor exists for future reward requests.',
                        ),
                    };

                    Log::channel('reward_processing')->debug('After reward processing. Starting final player updates.', [
                        'character_id' => $this->characterId,
                        'request_id' => $request->id,
                        'source_type' => $request->source_type?->value,
                        'elapsed_ms' => intdiv(hrtime(true) - $loopStartedAtNs, 1_000_000),
                        'memory_usage' => memory_get_usage(true),
                    ]);

                    $this->runFinalPlayerUpdatesStep(
                        $request,
                        $battleRewardLedgerService,
                        $request->source_type,
                        $battleRewardLiveUpdateService,
                        $explorationLogService,
                    );

                    $secondaryPlayerUpdatesDirty = true;

                    $this->runMessageOutboxStep(
                        $request,
                        $battleRewardLedgerService,
                        $battleRewardMessageOutboxService,
                    );

                    $queueManager->markCompleted($request);

                    $requestResult = 'completed';

                    Log::channel('reward_processing')->debug('Final player updates finished.', [
                        'character_id' => $this->characterId,
                        'request_id' => $request->id,
                    ]);
                } catch (Throwable $exception) {
                    if ($exception instanceof MissingInventoryException) {
                        Character::find($this->characterId)?->user()->update(['will_be_deleted' => true]);
                        $queueManager->markCorruptedInventory($request);
                        Log::channel('reward_processing')->warning('Battle reward processing stopped for a character with missing inventory.', [
                            'character_id' => $this->characterId,
                            'request_id' => $request->id,
                            'source_type' => $request->source_type?->value ?? 'unknown',
                            'exception' => $exception,
                        ]);
                        $capacityRetryPaused = true;
                        $requestResult = 'capacity_paused';

                        break;
                    }

                    Log::channel('reward_processing')->error('Exception caught during reward processing.', [
                        'character_id' => $this->characterId,
                        'request_id' => $request->id,
                        'source_type' => $request->source_type?->value ?? 'unknown',
                        'exception_class' => $exception::class,
                        'exception_message' => $exception->getMessage(),
                        'elapsed_ms' => intdiv(hrtime(true) - $loopStartedAtNs, 1_000_000),
                    ]);

                    if ($request->refresh()->status !== BattleRewardRequestStatus::COMPLETED) {
                        $activeStep = $request->steps()
                            ->whereIn('status', [
                                BattleRewardStepStatus::RUNNING,
                                BattleRewardStepStatus::CHECKPOINTED,
                                BattleRewardStepStatus::RESUMABLE,
                                BattleRewardStepStatus::FAILED,
                            ])
                            ->orderByDesc('id')
                            ->first();

                        if ($exception instanceof WeeklyRewardInventoryFullException
                            && $activeStep?->step_name === BattleRewardStepName::WEEKLY_REWARDS) {
                            $queueManager->markNotificationRetryable($request, $activeStep, $exception);
                            $notificationRetryScheduled = true;
                            $capacityRetryPaused = true;
                            $requestResult = 'retryable';
                        } elseif (! is_null($activeStep) && in_array($activeStep->step_name, [
                            BattleRewardStepName::FINAL_PLAYER_UPDATES,
                            BattleRewardStepName::MESSAGE_OUTBOX,
                        ], true)) {
                            $queueManager->markNotificationRetryable($request, $activeStep, $exception);
                            $notificationRetryScheduled = true;
                            $shouldDispatchAfterUnlock = true;
                            $requestResult = 'retryable';
                        } else {
                            if (! is_null($activeStep)) {
                                $battleRewardLedgerService->failStep($activeStep, $exception);
                            }

                            $queueManager->markFailed($request, $exception);
                            $requestResult = 'failed';
                        }
                    }

                    if (! $exception instanceof WeeklyRewardInventoryFullException) {
                        (new MonitoredBugReportService)->reportError(
                            'battle-reward-queue',
                            $exception->getMessage(),
                            ['character_id' => $this->characterId, 'source_type' => $request->source_type?->value ?? 'unknown'],
                            $exception::class,
                            $this->characterId,
                        );
                    }
                } finally {
                    Log::channel('reward_processing')->info('Request processing summary.', [
                        'character_id' => $this->characterId,
                        'request_id' => $request->id,
                        'source_type' => $request->source_type?->value,
                        'priority' => $request->priority?->value,
                        'queue_wait_ms' => $request->created_at->diffInMilliseconds($request->started_at),
                        'processing_ms' => intdiv(hrtime(true) - $requestStartedAtNs, 1_000_000),
                        'result' => $requestResult,
                    ]);
                }

                $processed++;

                if ($notificationRetryScheduled) {
                    break;
                }
            }

            if ($capacityRetryPaused || $queueManager->hasProcessingRequests($this->characterId)) {
                return;
            }

            if ($queueManager->hasPendingRequests($this->characterId)) {
                Log::channel('reward_processing')->info('Pending rows remain after loop. Continuation needed.', [
                    'character_id' => $this->characterId,
                    'processed' => $processed,
                    'elapsed_ms' => intdiv(hrtime(true) - $loopStartedAtNs, 1_000_000),
                ]);

                $queueManager->updateHeartbeat($this->characterId);
                $shouldDispatchAfterUnlock = true;
            } else {
                $markedInactive = $queueManager->markQueueInactiveIfEmpty($this->characterId);

                if (! $markedInactive) {
                    $shouldDispatchAfterUnlock = true;
                } else {
                    $shouldCheckPendingAfterUnlock = true;
                }
            }
        } finally {
            $processorLock->release();

            Log::channel('reward_processing')->debug('Lock released.', [
                'character_id' => $this->characterId,
                'lock_key' => $lockKey,
                'processed' => $processed,
            ]);

            if ($secondaryPlayerUpdatesDirty) {
                DispatchBattleRewardSecondaryUpdates::dispatch($this->characterId)
                    ->onConnection('battle_reward_processing')
                    ->onQueue('battle_reward_secondary');
            }
        }

        if ($shouldCheckPendingAfterUnlock && $queueManager->hasPendingRequests($this->characterId)) {
            Log::channel('reward_processing')->info('Pending row appeared between empty check and lock release. Continuation needed.', [
                'character_id' => $this->characterId,
            ]);

            $queueManager->updateHeartbeat($this->characterId);
            $shouldDispatchAfterUnlock = true;
        }

        if ($shouldDispatchAfterUnlock) {
            Log::channel('reward_processing')->info('Continuation dispatched after lock release.', [
                'character_id' => $this->characterId,
            ]);

            self::dispatch($this->characterId)
                ->onConnection('battle_reward_processing')
                ->onQueue('battle_reward_processing');
        }
    }

    /**
     * Run the request's FINAL_PLAYER_UPDATES ledger step, dispatching the source-specific live update.
     *
     * @param CharacterBattleRewardRequest $request
     * @param BattleRewardLedgerService $battleRewardLedgerService
     * @param BattleRewardRequestSourceType $sourceType
     * @param BattleRewardLiveUpdateService $battleRewardLiveUpdateService
     * @param ExplorationLogService $explorationLogService
     * @return void
     */
    private function runFinalPlayerUpdatesStep(
        CharacterBattleRewardRequest $request,
        BattleRewardLedgerService $battleRewardLedgerService,
        BattleRewardRequestSourceType $sourceType,
        BattleRewardLiveUpdateService $battleRewardLiveUpdateService,
        ExplorationLogService $explorationLogService,
    ): void {
        $step = $request->steps()
            ->where('step_name', BattleRewardStepName::FINAL_PLAYER_UPDATES)
            ->first();

        if (is_null($step)) {
            throw new RuntimeException(
                'Reward request '.$request->id.' has no FINAL_PLAYER_UPDATES ledger row. The ledger is incomplete.',
            );
        }

        if ($step->status === BattleRewardStepStatus::COMPLETED) {
            $battleRewardLedgerService->log('step.skipped_completed', $request, $step);

            return;
        }

        $step = $battleRewardLedgerService->startStep($step);

        try {
            $this->dispatchFinalPlayerUpdates(
                $sourceType,
                $battleRewardLiveUpdateService,
                $explorationLogService,
            );

            $battleRewardLedgerService->completeStep($step);
        } catch (Throwable $throwable) {
            $battleRewardLedgerService->failStep($step, $throwable);

            throw $throwable;
        }
    }

    /**
     * Process the ledger-aware rewards for a single battle reward request.
     *
     * @param BattleRewardService $battleRewardService
     * @param CharacterBattleRewardRequest $request
     * @param ?BattleRewardSharedContext $sharedContext
     * @param callable $heartbeatCallback
     * @return void
     */
    private function processBattleRewardRequest(
        BattleRewardService $battleRewardService,
        CharacterBattleRewardRequest $request,
        ?BattleRewardSharedContext $sharedContext,
        callable $heartbeatCallback,
    ): void {
        $result = $battleRewardService
            ->withHeartbeatCallback($heartbeatCallback)
            ->processLedgerAwareRewards($request, $sharedContext);

        if ($result->successful()) {
            return;
        }

        $failure = $result->failure();

        if (is_null($failure)) {
            throw new RuntimeException(
                'Battle reward processing failed without a recorded failure for request '.$request->id.'.',
            );
        }

        throw $failure;
    }

    /**
     * Run the request's MESSAGE_OUTBOX ledger step, emitting any unemitted durable messages.
     *
     * @param CharacterBattleRewardRequest $request
     * @param BattleRewardLedgerService $battleRewardLedgerService
     * @param BattleRewardMessageOutboxService $battleRewardMessageOutboxService
     * @return void
     */
    private function runMessageOutboxStep(
        CharacterBattleRewardRequest $request,
        BattleRewardLedgerService $battleRewardLedgerService,
        BattleRewardMessageOutboxService $battleRewardMessageOutboxService,
    ): void {
        $step = $request->steps()
            ->where('step_name', BattleRewardStepName::MESSAGE_OUTBOX)
            ->firstOrFail();

        if ($step->status === BattleRewardStepStatus::COMPLETED) {
            $battleRewardLedgerService->log('step.skipped_completed', $request, $step);

            return;
        }

        $step = $battleRewardLedgerService->startStep($step);
        $emittedCount = $battleRewardMessageOutboxService->emitUnemittedMessages($request);
        $battleRewardLedgerService->completeStep($step, ['emitted_message_count' => $emittedCount]);
    }

    /**
     * Recover the Character's interrupted or orphaned reward requests after the job ultimately fails.
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        Log::channel('reward_processing')->error('Job failed method invoked by Laravel failure hook.', [
            'character_id' => $this->characterId,
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'job_attempt' => $this->attempts(),
        ]);

        $queueManager = new BattleRewardProcessingQueueManager();

        $ledgerRecoveredCount = $queueManager->recoverLedgerBackedProcessingRequests($this->characterId);
        $orphanRecoveredCount = $queueManager->recoverOrphanedProcessingRequests($this->characterId);

        Log::channel('reward_processing')->warning('Failed hook recovery result.', [
            'character_id' => $this->characterId,
            'ledger_recovered' => $ledgerRecoveredCount,
            'orphan_recovered' => $orphanRecoveredCount,
        ]);

        if ($queueManager->hasProcessingRequests($this->characterId)) {
            Log::channel('reward_processing')->debug('Failed hook: fresh legacy processing row still blocking; skipping dispatch.', [
                'character_id' => $this->characterId,
            ]);

            return;
        }

        if (! $queueManager->hasPendingRequests($this->characterId)) {
            Log::channel('reward_processing')->debug('Failed hook: no pending or resumable rows; skipping continuation dispatch.', [
                'character_id' => $this->characterId,
            ]);

            return;
        }

        if (config('queue.connections.battle_reward_processing.driver') === 'sync') {
            Log::channel('reward_processing')->warning('Failed hook skipped continuation dispatch to prevent recursive synchronous failure.', [
                'character_id' => $this->characterId,
                'connection' => 'battle_reward_processing',
                'driver' => 'sync',
            ]);

            return;
        }

        Log::channel('reward_processing')->info('Failed hook dispatching processor for remaining rows.', [
            'character_id' => $this->characterId,
        ]);

        self::dispatch($this->characterId)
            ->onConnection('battle_reward_processing')
            ->onQueue('battle_reward_processing');
    }

    /**
     * Dispatch the authoritative live player update, plus any source-specific output update.
     *
     * @param BattleRewardRequestSourceType $sourceType
     * @param BattleRewardLiveUpdateService $battleRewardLiveUpdateService
     * @param ExplorationLogService $explorationLogService
     * @return void
     */
    private function dispatchFinalPlayerUpdates(
        BattleRewardRequestSourceType $sourceType,
        BattleRewardLiveUpdateService $battleRewardLiveUpdateService,
        ExplorationLogService $explorationLogService,
    ): void {
        $character = Character::find($this->characterId)?->refresh();

        if (is_null($character)) {
            return;
        }

        Log::channel('reward_processing')->debug('Authoritative live reward update attempted.', [
            'character_id' => $this->characterId,
        ]);

        $battleRewardLiveUpdateService->broadcast($this->characterId);

        if ($sourceType === BattleRewardRequestSourceType::EXPLORATION) {
            Log::channel('reward_processing')->debug('Exploration output update attempted.', [
                'character_id' => $this->characterId,
            ]);

            try {
                $explorationLogService->outputForCharacter($character);
            } catch (Throwable $throwable) {
                Log::channel('reward_processing')->warning('Exploration output update failed. Reward row will not be marked failed.', [
                    'character_id' => $this->characterId,
                    'exception_class' => $throwable::class,
                    'exception_message' => $throwable->getMessage(),
                ]);

                Log::warning('Unable to dispatch exploration reward queue update.', [
                    'character_id' => $this->characterId,
                    'exception_class' => $throwable::class,
                    'exception' => $throwable->getMessage(),
                ]);
            }
        }

        if ($sourceType === BattleRewardRequestSourceType::AUTOMATION) {
            Log::channel('reward_processing')->debug('Delve status update attempted.', [
                'character_id' => $this->characterId,
            ]);

            $this->safelyDispatchBroadcastEvent(
                new DelveStatusUpdated($character->user_id),
                ['character_id' => $this->characterId]
            );
        }
    }

    /**
     * Process the NPC Quest reward for the Character and announce its completion.
     *
     * @param NpcQuestRewardHandler $npcQuestRewardHandler
     * @param int $questId
     * @return void
     */
    private function processQuestReward(
        NpcQuestRewardHandler $npcQuestRewardHandler,
        int $questId,
    ): void {
        $character = Character::findOrFail($this->characterId);
        $quest = Quest::findOrFail($questId);

        $npcQuestRewardHandler->processReward($quest, $quest->npc, $character);

        event(new GlobalMessageEvent(
            $character->name.' Has completed a quest ('.$quest->name.') for: '
            .$quest->npc->real_name.' and been rewarded with a godly gift!',
        ));
    }
}
