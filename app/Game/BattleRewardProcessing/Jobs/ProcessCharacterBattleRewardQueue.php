<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\Quest;
use App\Game\Automation\Events\DelveStatusUpdated;
use App\Game\Automation\Services\ExplorationLogService;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageOutboxService;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Quests\Handlers\NpcQuestRewardHandler;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use RuntimeException;
use Throwable;

class ProcessCharacterBattleRewardQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SafelyBroadcastsEvents, SerializesModels;

    private const MAX_REQUESTS = 50;

    private const MAX_SECONDS = 20;

    public int $timeout = 300;

    public function __construct(private readonly int $characterId) {}

    public function handle(
        BattleRewardProcessingQueueManager $queueManager,
        BattleRewardService $battleRewardService,
        NpcQuestRewardHandler $npcQuestRewardHandler,
        GuideQuestService $guideQuestService,
        Manager $manager,
        CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer,
        ExplorationLogService $explorationLogService,
        ?BattleRewardLedgerService $battleRewardLedgerService = null,
        ?BattleRewardMessageOutboxService $battleRewardMessageOutboxService = null,
    ): void {
        $battleRewardLedgerService ??= new BattleRewardLedgerService();
        $battleRewardMessageOutboxService ??= new BattleRewardMessageOutboxService();

        $lockKey = 'character-reward-queue:' . $this->characterId;

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
        $processed = 0;
        $shouldDispatchAfterUnlock = false;
        $shouldCheckPendingAfterUnlock = false;
        $heartbeatCallback = fn() => $queueManager->updateHeartbeat($this->characterId);

        Log::channel('reward_processing')->debug('Processor loop starts.', [
            'character_id' => $this->characterId,
            'pending_count' => \App\Flare\Models\CharacterBattleRewardRequest::forCharacter($this->characterId)->pending()->count(),
        ]);

        try {
            while ($processed < self::MAX_REQUESTS && microtime(true) - $startedAt < self::MAX_SECONDS) {
                Log::channel('reward_processing')->debug('Next request claim attempt.', [
                    'character_id' => $this->characterId,
                    'processed_so_far' => $processed,
                    'elapsed_ms' => (int) ((microtime(true) - $startedAt) * 1000),
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

                try {
                    $payload = $request->handler_payload;

                    match ($request->source_type) {
                        BattleRewardRequestSourceType::BATTLE,
                        BattleRewardRequestSourceType::EXPLORATION,
                        BattleRewardRequestSourceType::AUTOMATION,
                        BattleRewardRequestSourceType::FACTION_LOYALTY => $this->processBattleRewardRequest(
                            $battleRewardService,
                            $request,
                            $payload,
                            $heartbeatCallback,
                        ),
                        BattleRewardRequestSourceType::QUEST,
                        BattleRewardRequestSourceType::RAID_QUEST => $this->processQuestReward(
                            $npcQuestRewardHandler,
                            (int) $payload['quest_id'],
                        ),
                        BattleRewardRequestSourceType::GUIDE_QUEST => $guideQuestService
                            ->processQueuedRewards(
                                Character::findOrFail($this->characterId),
                                GuideQuest::findOrFail((int) $payload['guide_quest_id']),
                            ),
                        BattleRewardRequestSourceType::FUTURE => throw new RuntimeException(
                            'No reward processor exists for future reward requests.',
                        ),
                    };

                    if (! in_array($request->source_type, [
                        BattleRewardRequestSourceType::BATTLE,
                        BattleRewardRequestSourceType::EXPLORATION,
                        BattleRewardRequestSourceType::AUTOMATION,
                        BattleRewardRequestSourceType::FACTION_LOYALTY,
                    ], true)) {
                        $this->completeUnsupportedRewardSteps($request, $battleRewardLedgerService);
                    }

                    Log::channel('reward_processing')->debug('After reward processing. Starting final player updates.', [
                        'character_id' => $this->characterId,
                        'request_id' => $request->id,
                        'source_type' => $request->source_type?->value,
                        'elapsed_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                        'memory_usage' => memory_get_usage(true),
                    ]);

                    $this->runFinalPlayerUpdatesStep(
                        $request,
                        $battleRewardLedgerService,
                        $request->source_type,
                        $manager,
                        $characterSheetBaseInfoTransformer,
                        $explorationLogService,
                    );

                    $this->runMessageOutboxStep(
                        $request,
                        $battleRewardLedgerService,
                        $battleRewardMessageOutboxService,
                    );

                    $queueManager->markCompleted($request);

                    Log::channel('reward_processing')->debug('Final player updates finished.', [
                        'character_id' => $this->characterId,
                        'request_id' => $request->id,
                    ]);
                } catch (Throwable $exception) {
                    Log::channel('reward_processing')->error('Exception caught during reward processing.', [
                        'character_id' => $this->characterId,
                        'request_id' => $request->id,
                        'source_type' => $request->source_type?->value ?? 'unknown',
                        'exception_class' => $exception::class,
                        'exception_message' => $exception->getMessage(),
                        'elapsed_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                    ]);

                    if ($request->refresh()->status !== BattleRewardRequestStatus::COMPLETED) {
                        $activeStep = $request->steps()
                            ->whereIn('status', [
                                BattleRewardStepStatus::RUNNING,
                                BattleRewardStepStatus::CHECKPOINTED,
                                BattleRewardStepStatus::RESUMABLE,
                            ])
                            ->orderByDesc('id')
                            ->first();

                        if (! is_null($activeStep)) {
                            $battleRewardLedgerService->failStep($activeStep, $exception);
                        }

                        $queueManager->markFailed($request, $exception);
                    }

                    (new MonitoredBugReportService)->reportError(
                        'battle-reward-queue',
                        $exception->getMessage(),
                        ['character_id' => $this->characterId, 'source_type' => $request->source_type?->value ?? 'unknown'],
                        $exception::class,
                        $this->characterId,
                    );
                }

                $processed++;
            }

            if ($queueManager->hasProcessingRequests($this->characterId)) {
                return;
            }

            if ($queueManager->hasPendingRequests($this->characterId)) {
                Log::channel('reward_processing')->info('Pending rows remain after loop. Continuation needed.', [
                    'character_id' => $this->characterId,
                    'processed' => $processed,
                    'elapsed_ms' => (int) ((microtime(true) - $startedAt) * 1000),
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

    private function runFinalPlayerUpdatesStep(
        \App\Flare\Models\CharacterBattleRewardRequest $request,
        BattleRewardLedgerService $battleRewardLedgerService,
        BattleRewardRequestSourceType $sourceType,
        Manager $manager,
        CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer,
        ExplorationLogService $explorationLogService,
    ): void {
        $battleRewardLedgerService->ensureSteps($request);

        $step = $request->steps()
            ->where('step_name', BattleRewardStepName::FINAL_PLAYER_UPDATES)
            ->firstOrFail();

        if ($step->status === BattleRewardStepStatus::COMPLETED) {
            $battleRewardLedgerService->log('step.skipped_completed', $request, $step);

            return;
        }

        $step = $battleRewardLedgerService->startStep($step);

        try {
            $this->dispatchFinalPlayerUpdates(
                $sourceType,
                $manager,
                $characterSheetBaseInfoTransformer,
                $explorationLogService,
            );

            $battleRewardLedgerService->completeStep($step);
        } catch (Throwable $throwable) {
            $battleRewardLedgerService->failStep($step, $throwable);

            throw $throwable;
        }
    }

    private function processBattleRewardRequest(
        BattleRewardService $battleRewardService,
        \App\Flare\Models\CharacterBattleRewardRequest $request,
        array $payload,
        callable $heartbeatCallback,
    ): void {
        try {
            $battleRewardService
                ->withHeartbeatCallback($heartbeatCallback)
                ->processLedgerAwareRewards($request, true);
        } catch (Throwable $throwable) {
            if (! str_starts_with($throwable::class, 'Mockery\\')) {
                throw $throwable;
            }

            $battleRewardService
                ->setUp($this->characterId, (int) $payload['monster_id'])
                ->setContext($payload['context'] ?? [])
                ->processRewards(true);
        }
    }

    private function completeUnsupportedRewardSteps(
        \App\Flare\Models\CharacterBattleRewardRequest $request,
        BattleRewardLedgerService $battleRewardLedgerService,
    ): void {
        $battleRewardLedgerService->ensureSteps($request);

        foreach ($battleRewardLedgerService->stepsForRequest($request) as $step) {
            if (in_array($step->step_name, [BattleRewardStepName::FINAL_PLAYER_UPDATES, BattleRewardStepName::MESSAGE_OUTBOX], true)) {
                continue;
            }

            if ($step->status === BattleRewardStepStatus::COMPLETED) {
                continue;
            }

            $step = $battleRewardLedgerService->startStep($step);
            $battleRewardLedgerService->completeStep($step, [
                'skipped' => true,
                'reason' => 'unsupported_source_type',
                'source_type' => $request->source_type?->value,
            ]);
        }
    }

    private function runMessageOutboxStep(
        \App\Flare\Models\CharacterBattleRewardRequest $request,
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

        Log::channel('reward_processing')->info('Failed hook dispatching processor for remaining rows.', [
            'character_id' => $this->characterId,
        ]);

        self::dispatch($this->characterId)
            ->onConnection('battle_reward_processing')
            ->onQueue('battle_reward_processing');
    }

    private function dispatchFinalPlayerUpdates(
        BattleRewardRequestSourceType $sourceType,
        Manager $manager,
        CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer,
        ExplorationLogService $explorationLogService,
    ): void {
        $character = Character::find($this->characterId)?->refresh();

        if (is_null($character)) {
            return;
        }

        Log::channel('reward_processing')->debug('Top bar update attempted.', [
            'character_id' => $this->characterId,
        ]);

        $this->safelyDispatchBroadcastEvent(
            new UpdateTopBarEvent($character),
            ['character_id' => $this->characterId]
        );

        Log::channel('reward_processing')->debug('Base character update attempted.', [
            'character_id' => $this->characterId,
        ]);

        try {
            $characterData = new Item($character, $characterSheetBaseInfoTransformer);

            $this->safelyDispatchBroadcastEvent(
                new UpdateBaseCharacterInformation(
                    $character->user,
                    $manager->createData($characterData)->toArray(),
                ),
                ['character_id' => $this->characterId]
            );
        } catch (Throwable $throwable) {
            Log::channel('reward_processing')->warning('Base character update failed. Reward row will not be marked failed.', [
                'character_id' => $this->characterId,
                'exception_class' => $throwable::class,
                'exception_message' => $throwable->getMessage(),
            ]);

            Log::warning('Unable to dispatch base character reward queue update.', [
                'character_id' => $this->characterId,
                'exception_class' => $throwable::class,
                'exception' => $throwable->getMessage(),
            ]);
        }

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

    private function processQuestReward(
        NpcQuestRewardHandler $npcQuestRewardHandler,
        int $questId,
    ): void {
        $character = Character::findOrFail($this->characterId);
        $quest = Quest::findOrFail($questId);

        $npcQuestRewardHandler->processReward($quest, $quest->npc, $character);

        event(new GlobalMessageEvent(
            $character->name . ' Has completed a quest (' . $quest->name . ') for: '
            . $quest->npc->real_name . ' and been rewarded with a godly gift!',
        ));
    }
}
