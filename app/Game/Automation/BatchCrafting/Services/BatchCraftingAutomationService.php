<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Admin\Events\BatchCraftingMonitoringUpdated;
use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Events\BatchCraftingStatusUpdated;
use App\Game\Automation\BatchCrafting\Factories\BatchCraftingOrchestratorFactory;
use App\Game\Automation\BatchCrafting\Jobs\BatchCraftingJob;
use App\Game\Automation\BatchCrafting\Services\Setup\BatchCraftingSetupResolver;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Handlers\ServerMessageHandler;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class BatchCraftingAutomationService
{
    use ResponseBuilder;

    public function __construct(
        private readonly BatchCraftingSetupResolver $setupResolver,
        private readonly BatchCraftingModeService $batchCraftingModeService,
        private readonly BatchCraftingOrchestratorFactory $orchestratorFactory,
        private readonly ServerMessageHandler $serverMessageHandler,
        private readonly MonitoredBugReportService $monitoredBugReportService,
        private readonly BatchCraftingStatusService $batchCraftingStatusService,
    ) {}

    /**
     * Build the preview result for the validated Batch Crafting request, for modes that support a preview.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array The ResponseBuilder success or error result carrying the preview payload.
     */
    public function preview(Character $character, array $validated): array
    {
        $type = BatchCraftingType::from($validated['batch_type']);
        $preview = $this->setupResolver->resolve($type)->preview($character, $validated);

        if (is_null($preview)) {
            return $this->errorResult('A preview is not available for this craft mode.');
        }

        return $this->successResult($preview);
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

        $type = BatchCraftingType::from($validated['batch_type']);
        $resolved = $this->setupResolver->resolve($type)->resolveStart($character, $validated);

        if (! empty($resolved['blockers'])) {
            return $this->errorResult($resolved['blockers'][0]);
        }

        $scheduledFor = now()->addMinute();

        $progress = $resolved['progress'];
        $progress['scheduled_for'] = $scheduledFor->toJSON();
        $progress['processing_started_at'] = null;
        $progress['gold_spent_total'] = 0;
        $progress['gold_gained_total'] = 0;
        $progress['gold_dust_spent_total'] = 0;
        $progress['shards_spent_total'] = 0;
        $progress['copper_coins_spent_total'] = 0;
        $progress['disenchanted_count'] = 0;
        $progress['used_count'] = 0;
        $progress['chart_points'] = [];

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
        $this->broadcastStatus($character);
        event(new BatchCraftingMonitoringUpdated($character->id));

        BatchCraftingJob::dispatch($batchCrafting->id)->delay($scheduledFor);

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

        $this->broadcastStatus($character);
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
        return $this->successResult($this->batchCraftingStatusService->build($character));
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
     * Process the active Batch Crafting run's current execution window.
     *
     * Continuous modes process every operation to completion in this call. Recurring modes
     * process up to their fixed window size and, when still running, return the next
     * execution time for the caller to schedule.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record to process.
     * @return Carbon|null The next execution time when a recurring window remains, otherwise null.
     */
    public function process(BatchCrafting $batchCrafting): ?Carbon
    {
        $batchCrafting = $batchCrafting->fresh();

        if (is_null($batchCrafting) || ! $batchCrafting->isRunning()) {
            return null;
        }

        $type = BatchCraftingType::from($batchCrafting->batch_type);
        $modeValue = $type->modeFromProgress($batchCrafting->progress);

        $this->beginProcessingWindow($batchCrafting);

        $windowSize = $this->batchCraftingModeService->executionWindowSize($type, $modeValue);
        $operations = 0;

        while (is_null($windowSize) || $operations < $windowSize) {
            $stillRunning = $this->processNextAttempt($batchCrafting);
            $operations++;

            if (! $stillRunning) {
                return null;
            }
        }

        return $this->scheduleNextWindow($batchCrafting);
    }

    /**
     * Persist and broadcast the processing state at the start of an execution window.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record beginning its execution window.
     * @return void This method does not return a value.
     */
    private function beginProcessingWindow(BatchCrafting $batchCrafting): void
    {
        $progress = $batchCrafting->progress;
        $isFirstWindow = is_null($progress['processing_started_at']);
        $isResumingFromWait = ! $isFirstWindow && ! is_null($progress['next_attempt_at'] ?? null);

        if (! $isFirstWindow && ! $isResumingFromWait) {
            return;
        }

        if ($isFirstWindow) {
            $progress['processing_started_at'] = now()->toJSON();
        }

        $progress['next_attempt_at'] = null;
        $batchCrafting->update(['progress' => $progress]);
        $batchCrafting->refresh();

        $this->broadcastStatus($batchCrafting->character);
        event(new BatchCraftingMonitoringUpdated($batchCrafting->character_id));
    }

    /**
     * Persist the recurring waiting state and broadcast it, returning the next execution time.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record whose execution window just finished.
     * @return Carbon|null The next execution time, or null when the batch is no longer running.
     */
    private function scheduleNextWindow(BatchCrafting $batchCrafting): ?Carbon
    {
        $batchCrafting = $batchCrafting->fresh();

        if (is_null($batchCrafting) || ! $batchCrafting->isRunning()) {
            return null;
        }

        $nextAttemptAt = now()->addMinute();
        $progress = $batchCrafting->progress;
        $progress['next_attempt_at'] = $nextAttemptAt->toJSON();
        $batchCrafting->update(['progress' => $progress]);
        $batchCrafting->refresh();

        $this->broadcastStatus($batchCrafting->character);
        event(new BatchCraftingMonitoringUpdated($batchCrafting->character_id));

        return $nextAttemptAt;
    }

    /**
     * Run one Batch Crafting attempt against the freshly reloaded run, observing external cancellation.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record being processed.
     * @return bool True when another attempt should run immediately, false when processing has stopped.
     */
    private function processNextAttempt(BatchCrafting $batchCrafting): bool
    {
        $batchCrafting = $batchCrafting->fresh();

        if (is_null($batchCrafting) || ! $batchCrafting->isRunning()) {
            return false;
        }

        $character = $batchCrafting->character;

        if ($character->is_dead) {
            $this->completeBatch($batchCrafting, BatchCraftingEndReason::DIED);

            return false;
        }

        if (now()->greaterThanOrEqualTo($batchCrafting->ends_at)) {
            $this->completeBatch($batchCrafting, BatchCraftingEndReason::COMPLETED_DURATION);

            return false;
        }

        try {
            $type = BatchCraftingType::from($batchCrafting->batch_type);
            $orchestrator = $this->orchestratorFactory->make($type);
            $result = $orchestrator->orchestrate($batchCrafting, $character);
        } catch (Throwable $throwable) {
            $this->reportOperationFailure($batchCrafting, $throwable);
            $this->completeBatch($batchCrafting, BatchCraftingEndReason::FAILED);

            return false;
        }

        return $this->applyOperationResult($batchCrafting, $result);
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
     * Apply the result of one Batch Crafting attempt to the running batch.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record being processed.
     * @param  BatchCraftingOperationResult  $result  The outcome of the handled attempt.
     * @return bool True when another attempt should run immediately, false when processing has stopped.
     */
    private function applyOperationResult(BatchCrafting $batchCrafting, BatchCraftingOperationResult $result): bool
    {
        $actionStatus = $result->actionStatus();
        $chartPoint = null;

        if (! is_null($actionStatus)) {
            $this->incrementActionCounters($batchCrafting, $actionStatus);
            $this->incrementDisposalCounters($batchCrafting, $result);
            $chartPoint = $this->recordChartPoint($batchCrafting, $result, $actionStatus);
        }

        $batchCrafting = $batchCrafting->fresh();
        $endReason = $result->endReason();

        if (! is_null($endReason)) {
            $this->completeBatch($batchCrafting, $endReason, $chartPoint);

            return false;
        }

        $this->broadcastStatus($batchCrafting->character, $chartPoint);
        event(new BatchCraftingMonitoringUpdated($batchCrafting->character_id));

        return true;
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

        if ($actionStatus === BatchCraftingActionStatus::SKIPPED) {
            $batchCrafting->increment('skipped_count');

            return;
        }

        if ($actionStatus === BatchCraftingActionStatus::APPLIED) {
            $batchCrafting->increment('applied_count');

            return;
        }

        if ($actionStatus === BatchCraftingActionStatus::DISENCHANTED) {
            $batchCrafting->increment('crafted_count');
            $this->incrementProgressCounter($batchCrafting, 'disenchanted_count');

            return;
        }

        if ($actionStatus === BatchCraftingActionStatus::USED) {
            $batchCrafting->increment('crafted_count');
            $this->incrementProgressCounter($batchCrafting, 'used_count');

            return;
        }

        $column = match ($actionStatus) {
            BatchCraftingActionStatus::KEPT => 'kept_count',
            BatchCraftingActionStatus::SOLD => 'sold_count',
            BatchCraftingActionStatus::DESTROYED => 'destroyed_count',
            BatchCraftingActionStatus::LISTED => 'listed_count',
            BatchCraftingActionStatus::CRAFTED => 'crafted_count',
        };

        $batchCrafting->increment('crafted_count');

        if ($column !== 'crafted_count') {
            $batchCrafting->increment($column);
        }
    }

    /**
     * Increment a counter persisted within the batch's progress data.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record being updated.
     * @param  string  $key  The progress counter key to increment.
     * @return void This method does not return a value.
     */
    private function incrementProgressCounter(BatchCrafting $batchCrafting, string $key): void
    {
        $progress = $batchCrafting->fresh()->progress;
        $progress[$key] = ($progress[$key] ?? 0) + 1;
        $batchCrafting->update(['progress' => $progress]);
    }

    /**
     * Apply the operation's additional disposal counters, independently of the primary action counter.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record being updated.
     * @param  BatchCraftingOperationResult  $result  The outcome of the handled attempt.
     * @return void This method does not return a value.
     */
    private function incrementDisposalCounters(BatchCrafting $batchCrafting, BatchCraftingOperationResult $result): void
    {
        if ($result->additionalSoldCount() > 0) {
            $batchCrafting->increment('sold_count', $result->additionalSoldCount());
        }

        if ($result->additionalDestroyedCount() > 0) {
            $batchCrafting->increment('destroyed_count', $result->additionalDestroyedCount());
        }

        if ($result->additionalDisenchantedCount() > 0) {
            $this->incrementProgressCounter($batchCrafting, 'disenchanted_count');
        }
    }

    /**
     * Record the cumulative resource totals and append one chart point for an attempted operation.
     *
     * A skipped Event action slot never reaches this method, so no fake craft attempt is ever charted.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record being updated.
     * @param  BatchCraftingOperationResult  $result  The outcome of the attempted operation.
     * @param  BatchCraftingActionStatus  $actionStatus  The operation's recorded action status.
     * @return array|null The newly recorded chart point, or null for a skipped action slot.
     */
    private function recordChartPoint(BatchCrafting $batchCrafting, BatchCraftingOperationResult $result, BatchCraftingActionStatus $actionStatus): ?array
    {
        if ($actionStatus === BatchCraftingActionStatus::SKIPPED) {
            return null;
        }

        $progress = $batchCrafting->fresh()->progress;
        $progress['gold_spent_total'] += $result->goldSpent();
        $progress['gold_gained_total'] += $result->goldGained();
        $progress['gold_dust_spent_total'] = ($progress['gold_dust_spent_total'] ?? 0) + $result->goldDustSpent();
        $progress['shards_spent_total'] = ($progress['shards_spent_total'] ?? 0) + $result->shardsSpent();
        $progress['copper_coins_spent_total'] = ($progress['copper_coins_spent_total'] ?? 0) + $result->copperCoinsSpent();

        $chartPoint = [
            'occurred_at' => now()->toJSON(),
            'successful' => $batchCrafting->crafted_count,
            'failed' => $batchCrafting->failed_count,
            'gold_spent' => $progress['gold_spent_total'],
            'gold_gained' => $progress['gold_gained_total'],
            'gold_dust_spent' => $progress['gold_dust_spent_total'],
            'shards_spent' => $progress['shards_spent_total'],
            'copper_coins_spent' => $progress['copper_coins_spent_total'],
        ];

        $progress['chart_points'][] = $chartPoint;
        $batchCrafting->update(['progress' => $progress]);

        return $chartPoint;
    }

    /**
     * Complete the Batch Crafting run with the supplied end reason.
     *
     * @param  BatchCrafting  $batchCrafting  The Batch Crafting record being completed.
     * @param  BatchCraftingEndReason  $reason  The reason the run ended.
     * @param  array|null  $chartPoint  The latest chart point produced by the final attempt, when applicable.
     * @return void This method does not return a value.
     */
    private function completeBatch(BatchCrafting $batchCrafting, BatchCraftingEndReason $reason, ?array $chartPoint = null): void
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
        $batchCrafting->refresh();

        $this->broadcastStatus($batchCrafting->character, $chartPoint);
        event(new BatchCraftingMonitoringUpdated($batchCrafting->character_id));
    }

    /**
     * Build the authoritative broadcast status snapshot and dispatch it for the character's user.
     *
     * @param  Character  $character  The character whose status is being broadcast.
     * @param  array|null  $chartPoint  The latest chart point produced by this update, when one occurred.
     * @return void This method does not return a value.
     */
    private function broadcastStatus(Character $character, ?array $chartPoint = null): void
    {
        $status = $this->batchCraftingStatusService->buildForBroadcast($character);

        event(new BatchCraftingStatusUpdated($character->user_id, $status, $chartPoint));
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
     * Return the message sent when a Batch Crafting run is scheduled to begin.
     *
     * @return string The scheduled-start message text.
     */
    private function startScheduledMessage(): string
    {
        return 'Batch Crafting is scheduled and will begin in one minute.';
    }
}
