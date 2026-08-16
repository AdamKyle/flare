<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Admin\Events\BatchCraftingMonitoringUpdated;
use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Events\BatchCraftingStatusUpdated;
use App\Game\Automation\BatchCrafting\Factories\BatchCraftingOrchestratorFactory;
use App\Game\Automation\BatchCrafting\Jobs\BatchCraftingJob;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class BatchCraftingAutomationService
{
    use ResponseBuilder;

    /**
     * @param  CraftAmountPreviewService  $craftAmountPreviewService  The Craft Amount preview builder.
     * @param  BatchCraftingOrchestratorFactory  $orchestratorFactory  The batch type orchestrator resolver.
     * @param  ServerMessageHandler  $serverMessageHandler  The player server-message dispatcher.
     * @param  MonitoredBugReportService  $monitoredBugReportService  The monitored bug-report service.
     */
    public function __construct(
        private readonly CraftAmountPreviewService $craftAmountPreviewService,
        private readonly BatchCraftingOrchestratorFactory $orchestratorFactory,
        private readonly ServerMessageHandler $serverMessageHandler,
        private readonly MonitoredBugReportService $monitoredBugReportService,
    ) {}

    /**
     * Build the Craft Amount preview result for the validated Batch Crafting request.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array The ResponseBuilder success result carrying the preview payload.
     */
    public function preview(Character $character, array $validated): array
    {
        return $this->successResult($this->craftAmountPreviewService->build($character, $validated));
    }

    /**
     * Start a new Batch Crafting run for the character using the validated request.
     *
     * @param  Character  $character  The character starting the run.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array The ResponseBuilder success or error result.
     */
    public function start(Character $character, array $validated): array
    {
        if (! is_null($this->currentRunningBatchCrafting($character))) {
            return $this->errorResult('You already have a Batch Crafting run in progress.');
        }

        if ($character->isFactionLoyaltyAutomationRunning()) {
            return $this->errorResult('You cannot start Batch Crafting while Faction Loyalty automation is running.');
        }

        if ($character->is_dead) {
            return $this->errorResult('You cannot start Batch Crafting while dead.');
        }

        $preview = $this->craftAmountPreviewService->build($character, $validated);

        if (! empty($preview['blockers'])) {
            return $this->errorResult($preview['blockers'][0]);
        }

        $progress = $validated['progress'];
        $progress['craft_specific_count'] = 0;

        $batchCrafting = BatchCrafting::create([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => $validated['batch_type'],
            'disposition' => $validated['disposition'],
            'started_at' => now(),
            'ends_at' => now()->addHours(8),
            'status' => BatchCraftingStatus::RUNNING->value,
            'progress' => $progress,
        ]);

        $scheduledMessage = $this->startScheduledMessage();

        $this->serverMessageHandler->sendBasicMessage($character->user, $scheduledMessage);
        event(new AutomationLogUpdate($character->user_id, $scheduledMessage));
        event(new BatchCraftingStatusUpdated($character->user_id));
        event(new BatchCraftingMonitoringUpdated($character->id));

        BatchCraftingJob::dispatch($batchCrafting->id)->delay(now()->addMinute());

        return $this->successResult([
            'message' => 'Batch crafting has started.',
            'batch_crafting_id' => $batchCrafting->id,
        ]);
    }

    /**
     * Cancel the character's currently running Batch Crafting run.
     *
     * @param  Character  $character  The character cancelling the run.
     * @return array The ResponseBuilder success or error result.
     */
    public function cancel(Character $character): array
    {
        $batchCrafting = $this->currentRunningBatchCrafting($character);

        if (is_null($batchCrafting)) {
            return $this->errorResult('There is no Batch Crafting run to cancel.');
        }

        $this->completeBatch($batchCrafting, BatchCraftingEndReason::CANCELLED);

        return $this->successResult(['message' => 'Batch crafting has been cancelled.']);
    }

    /**
     * Dismiss the character's finished Batch Crafting panel.
     *
     * @param  Character  $character  The character dismissing the panel.
     * @return array The ResponseBuilder success or error result.
     */
    public function dismiss(Character $character): array
    {
        $batchCrafting = $this->currentVisibleBatchCrafting($character);

        if (is_null($batchCrafting) || $batchCrafting->isRunning()) {
            return $this->errorResult('There is no finished Batch Crafting run to dismiss.');
        }

        $batchCrafting->update(['panel_dismissed_at' => now()]);

        event(new BatchCraftingStatusUpdated($character->user_id));
        event(new BatchCraftingMonitoringUpdated($character->id));

        return $this->successResult(['message' => 'Batch crafting panel dismissed.']);
    }

    /**
     * Record that the character has acknowledged the Batch Crafting introduction.
     *
     * @param  Character  $character  The character acknowledging the introduction.
     * @return array The ResponseBuilder success result.
     */
    public function acknowledgeInfo(Character $character): array
    {
        BatchCrafting::updateOrCreate(
            [
                'character_id' => $character->id,
                'status' => BatchCraftingStatus::INFO->value,
            ],
            [
                'user_id' => $character->user_id,
                'batch_type' => BatchCraftingType::CRAFT->value,
                'disposition' => BatchCraftingDisposition::KEEP->value,
                'info_acknowledged' => true,
                'completed_at' => now(),
                'panel_dismissed_at' => now(),
            ]
        );

        return $this->successResult(['message' => 'Batch crafting information acknowledged.']);
    }

    /**
     * Return the character's current visible Batch Crafting panel status.
     *
     * @param  Character  $character  The character requesting status.
     * @return array The ResponseBuilder success result carrying the panel status.
     */
    public function status(Character $character): array
    {
        $batchCrafting = $this->currentVisibleBatchCrafting($character);
        $showInfo = ! $this->hasAcknowledgedInfo($character);

        if (is_null($batchCrafting)) {
            return $this->successResult([
                'active' => false,
                'is_running' => false,
                'is_visible' => false,
                'can_cancel' => false,
                'can_dismiss' => false,
                'show_info' => $showInfo,
                'batch' => null,
            ]);
        }

        $isRunning = $batchCrafting->isRunning();

        return $this->successResult([
            'active' => $isRunning,
            'is_running' => $isRunning,
            'is_visible' => true,
            'can_cancel' => $isRunning,
            'can_dismiss' => ! $isRunning,
            'show_info' => $showInfo,
            'batch' => $this->buildBatchStatus($batchCrafting),
        ]);
    }

    /**
     * Complete the character's running Batch Crafting run because the character died.
     *
     * @param  Character  $character  The character who died.
     * @return void This method does not return a value.
     */
    public function completeForDeath(Character $character): void
    {
        $batchCrafting = $this->currentRunningBatchCrafting($character);

        if (is_null($batchCrafting)) {
            return;
        }

        $this->completeBatch($batchCrafting, BatchCraftingEndReason::DIED);
    }

    /**
     * Process one queued Batch Crafting operation for the given run.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record to process.
     * @return void This method does not return a value.
     */
    public function processOneOperation(BatchCrafting $batchCrafting): void
    {
        $batchCrafting = $batchCrafting->fresh();

        if (is_null($batchCrafting) || ! $batchCrafting->isRunning()) {
            return;
        }

        $character = $batchCrafting->character;

        if ($character->is_dead) {
            $this->completeBatch($batchCrafting, BatchCraftingEndReason::DIED);

            return;
        }

        if (now()->greaterThanOrEqualTo($batchCrafting->ends_at)) {
            $this->completeBatch($batchCrafting, BatchCraftingEndReason::COMPLETED_DURATION);

            return;
        }

        try {
            $type = BatchCraftingType::from($batchCrafting->batch_type);
            $orchestrator = $this->orchestratorFactory->make($type);
            $result = $orchestrator->orchestrate($batchCrafting, $character);
        } catch (Throwable $throwable) {
            $this->reportOperationFailure($batchCrafting, $throwable);
            $this->completeBatch($batchCrafting, BatchCraftingEndReason::FAILED);

            return;
        }

        $this->applyOperationResult($batchCrafting, $result);
    }

    /**
     * Log and report an unexpected Batch Crafting operation failure.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record that failed.
     * @param  Throwable  $throwable  The unexpected failure.
     * @return void This method does not return a value.
     */
    private function reportOperationFailure(BatchCrafting $batchCrafting, Throwable $throwable): void
    {
        $context = [
            'batch_crafting_id' => $batchCrafting->id,
            'character_id' => $batchCrafting->character_id,
            'batch_type' => $batchCrafting->batch_type,
            'progress' => $batchCrafting->progress,
            'exception' => $throwable,
        ];

        Log::error('Batch Crafting operation failed.', $context);

        $this->monitoredBugReportService->reportError(
            'batch-crafting',
            $throwable->getMessage(),
            [
                'batch_crafting_id' => $batchCrafting->id,
                'batch_type' => $batchCrafting->batch_type,
                'progress' => $batchCrafting->progress,
            ],
            $throwable::class,
            $batchCrafting->character_id,
        );
    }

    /**
     * Apply the result of one Batch Crafting operation to the running batch.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record being processed.
     * @param  BatchCraftingOperationResult  $result  The outcome of the handled operation.
     * @return void This method does not return a value.
     */
    private function applyOperationResult(BatchCrafting $batchCrafting, BatchCraftingOperationResult $result): void
    {
        $endReason = $result->endReason();

        if (! is_null($endReason)) {
            $this->completeBatch($batchCrafting, $endReason);

            return;
        }

        $actionStatus = $result->actionStatus();

        $this->incrementActionCounters($batchCrafting, $actionStatus);

        $batchCrafting = $batchCrafting->fresh();

        if ($this->hasReachedRequestedAmount($batchCrafting)) {
            $this->completeBatch($batchCrafting, BatchCraftingEndReason::AMOUNT_REACHED);

            return;
        }

        event(new BatchCraftingStatusUpdated($batchCrafting->user_id));
        event(new BatchCraftingMonitoringUpdated($batchCrafting->character_id));

        if ($batchCrafting->isRunning()) {
            BatchCraftingJob::dispatch($batchCrafting->id)->delay(now()->addMinute());
        }
    }

    /**
     * Determine whether the running Batch Crafting row has reached its requested Craft Amount.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record being checked.
     * @return bool True when the requested amount has been reached.
     */
    private function hasReachedRequestedAmount(BatchCrafting $batchCrafting): bool
    {
        $progress = $batchCrafting->progress;

        return $progress['craft_specific_count'] >= $progress['craft_amount'];
    }

    /**
     * Increment the running batch's action counters for the given operation outcome.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record being updated.
     * @param  BatchCraftingActionStatus  $actionStatus  The operation's recorded action status.
     * @return void This method does not return a value.
     */
    private function incrementActionCounters(BatchCrafting $batchCrafting, BatchCraftingActionStatus $actionStatus): void
    {
        if ($actionStatus === BatchCraftingActionStatus::FAILED) {
            $batchCrafting->increment('failed_count');

            return;
        }

        $column = match ($actionStatus) {
            BatchCraftingActionStatus::KEPT => 'kept_count',
            BatchCraftingActionStatus::SOLD => 'sold_count',
            BatchCraftingActionStatus::DESTROYED => 'destroyed_count',
        };

        $batchCrafting->increment('crafted_count');
        $batchCrafting->increment($column);
    }

    /**
     * Complete the Batch Crafting run with the supplied end reason.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record being completed.
     * @param  BatchCraftingEndReason  $reason  The reason the run ended.
     * @return void This method does not return a value.
     */
    private function completeBatch(BatchCrafting $batchCrafting, BatchCraftingEndReason $reason): void
    {
        $attributes = [
            'status' => BatchCraftingStatus::COMPLETED->value,
            'completed_at' => now(),
            'ended_reason' => $reason->value,
        ];

        if ($reason === BatchCraftingEndReason::CANCELLED) {
            $attributes['cancelled_at'] = now();
        }

        $batchCrafting->update($attributes);

        event(new BatchCraftingStatusUpdated($batchCrafting->user_id));
        event(new BatchCraftingMonitoringUpdated($batchCrafting->character_id));
    }

    /**
     * Return the character's currently running Batch Crafting record, if any.
     *
     * @param  Character  $character  The character being checked.
     * @return BatchCrafting|null The running record, or null when nothing is running.
     */
    private function currentRunningBatchCrafting(Character $character): ?BatchCrafting
    {
        return BatchCrafting::where('character_id', $character->id)
            ->whereNull('completed_at')
            ->whereNull('cancelled_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Return the character's currently visible Batch Crafting record, if any.
     *
     * @param  Character  $character  The character being checked.
     * @return BatchCrafting|null The visible record, or null when nothing is visible.
     */
    private function currentVisibleBatchCrafting(Character $character): ?BatchCrafting
    {
        return BatchCrafting::where('character_id', $character->id)
            ->where('status', '!=', BatchCraftingStatus::INFO->value)
            ->where(function ($query) {
                $query->where(function ($runningQuery) {
                    $runningQuery->whereNull('completed_at')->whereNull('cancelled_at');
                })->orWhere(function ($endedQuery) {
                    $endedQuery->whereNull('panel_dismissed_at')
                        ->where(function ($reasonQuery) {
                            $reasonQuery->whereNotNull('completed_at')->orWhereNotNull('cancelled_at');
                        });
                });
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Determine whether the character has acknowledged the Batch Crafting introduction.
     *
     * @param  Character  $character  The character being checked.
     * @return bool True when the character has acknowledged the introduction.
     */
    private function hasAcknowledgedInfo(Character $character): bool
    {
        return BatchCrafting::where('character_id', $character->id)
            ->where('info_acknowledged', true)
            ->exists();
    }

    /**
     * Build the player-facing status payload for a visible Batch Crafting record.
     *
     * @param  BatchCrafting  $batchCrafting  The visible Batch Crafting record.
     * @return array The player-facing status payload.
     */
    private function buildBatchStatus(BatchCrafting $batchCrafting): array
    {
        $progress = $batchCrafting->progress;
        $requestedAmount = $progress['craft_amount'];
        $completedAmount = $progress['craft_specific_count'];
        $remainingAmount = max(0, $requestedAmount - $completedAmount);
        $currentItem = Item::find($progress['specific_item_id']);

        return [
            'id' => $batchCrafting->id,
            'batch_type' => $batchCrafting->batch_type,
            'craft_mode' => $progress['craft_mode'],
            'disposition' => $batchCrafting->disposition,
            'status' => $batchCrafting->status,
            'ended_reason' => $batchCrafting->ended_reason,
            'current_item_name' => $currentItem?->affix_name ?? $currentItem?->name,
            'output_destination' => $progress['output_destination'] ?? null,
            'requested_amount' => $requestedAmount,
            'completed_amount' => $completedAmount,
            'remaining_amount' => $remainingAmount,
            'gold_left' => $batchCrafting->character->gold,
        ];
    }

    /**
     * Return the message sent when a Batch Crafting run is scheduled to begin.
     *
     * @return string The scheduled-start message text.
     */
    private function startScheduledMessage(): string
    {
        return 'Batch Crafting is scheduled and will begin in one minute.';
    }
}
