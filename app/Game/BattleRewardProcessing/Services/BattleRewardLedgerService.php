<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestStep;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Values\BattleRewardStepPlan;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class BattleRewardLedgerService
{
    /**
     * Ensure the planned ledger rows exist for the request, bulk-inserting
     * them exactly once for a new request and returning the existing rows
     * unchanged when the ledger was already created.
     *
     * @param CharacterBattleRewardRequest $request
     * @param BattleRewardStepPlan $stepPlan
     * @return Collection
     */
    public function ensureSteps(CharacterBattleRewardRequest $request, BattleRewardStepPlan $stepPlan): Collection
    {
        if ($request->steps()->exists()) {
            return $this->stepsForRequest($request);
        }

        $now = now();

        $rows = array_map(fn (BattleRewardStepName $stepName): array => [
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $request->character_id,
            'step_name' => $stepName->value,
            'status' => BattleRewardStepStatus::PENDING->value,
            'created_at' => $now,
            'updated_at' => $now,
        ], $stepPlan->steps());

        CharacterBattleRewardRequestStep::query()->insertOrIgnore($rows);

        $this->log('ledger.created', $request, null, [
            'status' => 'created',
            'planned_step_count' => count($rows),
        ]);

        return $this->stepsForRequest($request);
    }

    /**
     * Resolve the request's persisted ledger rows in source-aware execution order.
     *
     * @param CharacterBattleRewardRequest $request
     * @return Collection
     */
    public function stepsForRequest(CharacterBattleRewardRequest $request): Collection
    {
        $stepsOrder = BattleRewardStepName::orderedForSource($request->source_type);

        $steps = $request->steps()->get()->keyBy(fn (CharacterBattleRewardRequestStep $step): string => $step->step_name->value);

        return collect($stepsOrder)
            ->map(fn (BattleRewardStepName $stepName): ?CharacterBattleRewardRequestStep => $steps->get($stepName->value))
            ->filter()
            ->values();
    }

    /**
     * Resolve the request's first non-completed persisted step, in source-aware execution order.
     *
     * @param CharacterBattleRewardRequest $request
     * @return ?CharacterBattleRewardRequestStep
     */
    public function firstNonCompletedStep(CharacterBattleRewardRequest $request): ?CharacterBattleRewardRequestStep
    {
        return $this->stepsForRequest($request)
            ->first(fn (CharacterBattleRewardRequestStep $step): bool => $step->status !== BattleRewardStepStatus::COMPLETED);
    }

    /**
     * Resolve the persisted result of an already-completed step for the
     * given request with a single direct query, so a later step can recover
     * its authoritative output on resume instead of depending on transient
     * in-memory state. Returns null when the step does not exist or has not
     * completed.
     *
     * @param CharacterBattleRewardRequest $request
     * @param BattleRewardStepName $stepName
     * @return ?array
     */
    public function completedStepResult(CharacterBattleRewardRequest $request, BattleRewardStepName $stepName): ?array
    {
        $step = CharacterBattleRewardRequestStep::query()
            ->where('character_battle_reward_request_id', $request->id)
            ->where('step_name', $stepName->value)
            ->where('status', BattleRewardStepStatus::COMPLETED->value)
            ->first();

        return $step?->result_json;
    }

    /**
     * Mark a step running, recording its payload and incrementing its attempt count.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @param ?array $payload
     * @return CharacterBattleRewardRequestStep
     */
    public function startStep(CharacterBattleRewardRequestStep $step, ?array $payload = null): CharacterBattleRewardRequestStep
    {
        if ($step->status === BattleRewardStepStatus::COMPLETED) {
            $this->log('step.skipped_completed', $step->request, $step);

            return $step;
        }

        $step->update([
            'status' => BattleRewardStepStatus::RUNNING,
            'payload_json' => $payload ?? $step->payload_json,
            'started_at' => $step->started_at ?? now(),
            'heartbeat_at' => now(),
            'failed_at' => null,
            'failed_reason' => null,
            'attempts' => $step->attempts + 1,
        ]);

        $this->log('step.started', $step->request, $step);

        return $step;
    }

    /**
     * Mark a step completed with its final result payload.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @param ?array $result
     * @return CharacterBattleRewardRequestStep
     */
    public function completeStep(CharacterBattleRewardRequestStep $step, ?array $result = null): CharacterBattleRewardRequestStep
    {
        $step->update([
            'status' => BattleRewardStepStatus::COMPLETED,
            'result_json' => $result ?? $step->result_json,
            'heartbeat_at' => now(),
            'completed_at' => now(),
            'failed_at' => null,
            'failed_reason' => null,
        ]);

        $this->log('step.completed', $step->request, $step);

        return $step;
    }

    /**
     * Record a mid-step checkpoint so a resumed step can continue from its last known progress.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @param array $checkpoint
     * @return CharacterBattleRewardRequestStep
     */
    public function checkpointStep(CharacterBattleRewardRequestStep $step, array $checkpoint): CharacterBattleRewardRequestStep
    {
        $step->update([
            'status' => BattleRewardStepStatus::CHECKPOINTED,
            'checkpoint_json' => $checkpoint,
            'heartbeat_at' => now(),
        ]);

        $this->log('step.checkpointed', $step->request, $step, [
            'checkpoint_summary' => implode(',', array_keys($checkpoint)),
        ]);

        return $step;
    }

    /**
     * Persist a step's planned payload before it is applied.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @param array $payload
     * @return CharacterBattleRewardRequestStep
     */
    public function updateStepPayload(CharacterBattleRewardRequestStep $step, array $payload): CharacterBattleRewardRequestStep
    {
        $step->update([
            'payload_json' => $payload,
            'heartbeat_at' => now(),
        ]);

        return $step;
    }

    /**
     * Refresh a step's heartbeat timestamp so it is not treated as stale during long-running work.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @return void
     */
    public function updateHeartbeat(CharacterBattleRewardRequestStep $step): void
    {
        $step->update(['heartbeat_at' => now()]);
    }

    /**
     * Mark a step failed with the given exception or reason.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @param Throwable|string $reason
     * @return CharacterBattleRewardRequestStep
     */
    public function failStep(CharacterBattleRewardRequestStep $step, Throwable|string $reason): CharacterBattleRewardRequestStep
    {
        $failedReason = $reason instanceof Throwable
            ? $reason::class.': '.$reason->getMessage()
            : $reason;

        $context = [];

        if ($reason instanceof Throwable) {
            $context = [
                'exception_class' => $reason::class,
                'exception_message' => $reason->getMessage(),
            ];
        }

        $step->update([
            'status' => BattleRewardStepStatus::FAILED,
            'failed_at' => now(),
            'failed_reason' => $failedReason,
            'heartbeat_at' => now(),
        ]);

        $this->log('step.failed', $step->request, $step, $context);

        return $step;
    }

    /**
     * Mark a running/checkpointed step resumable when its heartbeat is older than the given cutoff.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @param CarbonInterface $cutoff
     * @return bool
     */
    public function markStaleStepResumable(CharacterBattleRewardRequestStep $step, CarbonInterface $cutoff): bool
    {
        if (! in_array($step->status, [BattleRewardStepStatus::RUNNING, BattleRewardStepStatus::CHECKPOINTED], true)) {
            return false;
        }

        if (! is_null($step->heartbeat_at) && $step->heartbeat_at->gt($cutoff)) {
            return false;
        }

        $step->update([
            'status' => BattleRewardStepStatus::RESUMABLE,
            'heartbeat_at' => now(),
        ]);

        $this->log('step.resumable', $step->request, $step);

        return true;
    }

    /**
     * Write one structured ledger diagnostic log entry.
     *
     * @param string $event
     * @param CharacterBattleRewardRequest $request
     * @param ?CharacterBattleRewardRequestStep $step
     * @param array $context
     * @return void
     */
    public function log(string $event, CharacterBattleRewardRequest $request, ?CharacterBattleRewardRequestStep $step = null, array $context = []): void
    {
        Log::channel('reward_ledger')->debug($event, array_filter([
            'character_id' => $request->character_id,
            'request_id' => $request->id,
            'step_name' => $step?->step_name?->value,
            'status' => $step?->status?->value ?? $context['status'] ?? null,
            'source_type' => $request->source_type?->value,
            'source_id' => $request->source_id,
            'attempts' => $step?->attempts,
            'elapsed_ms' => $context['elapsed_ms'] ?? null,
            'checkpoint_summary' => $context['checkpoint_summary'] ?? null,
            'exception_class' => $context['exception_class'] ?? null,
            'exception_message' => $context['exception_message'] ?? null,
            'planned_step_count' => $context['planned_step_count'] ?? null,
        ], fn ($value): bool => ! is_null($value)));
    }
}
