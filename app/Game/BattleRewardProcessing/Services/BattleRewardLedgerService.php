<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestStep;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class BattleRewardLedgerService
{
    public function ensureSteps(CharacterBattleRewardRequest $request): Collection
    {
        $createdCount = 0;

        foreach (BattleRewardStepName::ordered() as $stepName) {
            $step = CharacterBattleRewardRequestStep::query()->firstOrCreate(
                [
                    'character_battle_reward_request_id' => $request->id,
                    'step_name' => $stepName,
                ],
                [
                    'character_id' => $request->character_id,
                    'status' => BattleRewardStepStatus::PENDING,
                ],
            );

            if ($step->wasRecentlyCreated) {
                $createdCount++;
                $this->log('step.created', $request, $step);
            }
        }

        if ($createdCount > 0) {
            $this->log('ledger.created', $request, null, ['status' => 'created']);
        }

        return $this->stepsForRequest($request);
    }

    public function stepsForRequest(CharacterBattleRewardRequest $request): Collection
    {
        $steps = $request->steps()->get()->keyBy(fn (CharacterBattleRewardRequestStep $step): string => $step->step_name->value);

        return collect(BattleRewardStepName::ordered())
            ->map(fn (BattleRewardStepName $stepName): ?CharacterBattleRewardRequestStep => $steps->get($stepName->value))
            ->filter()
            ->values();
    }

    public function firstNonCompletedStep(CharacterBattleRewardRequest $request): ?CharacterBattleRewardRequestStep
    {
        return $this->stepsForRequest($request)
            ->first(fn (CharacterBattleRewardRequestStep $step): bool => $step->status !== BattleRewardStepStatus::COMPLETED);
    }

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

        $step = $step->refresh();
        $this->log('step.started', $step->request, $step);

        return $step;
    }

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

        $step = $step->refresh();
        $this->log('step.completed', $step->request, $step);

        return $step;
    }

    public function checkpointStep(CharacterBattleRewardRequestStep $step, array $checkpoint): CharacterBattleRewardRequestStep
    {
        $step->update([
            'status' => BattleRewardStepStatus::CHECKPOINTED,
            'checkpoint_json' => $checkpoint,
            'heartbeat_at' => now(),
        ]);

        $step = $step->refresh();
        $this->log('step.checkpointed', $step->request, $step, [
            'checkpoint_summary' => implode(',', array_keys($checkpoint)),
        ]);

        return $step;
    }

    public function updateStepPayload(CharacterBattleRewardRequestStep $step, array $payload): CharacterBattleRewardRequestStep
    {
        $step->update([
            'payload_json' => $payload,
            'heartbeat_at' => now(),
        ]);

        return $step->refresh();
    }

    public function updateHeartbeat(CharacterBattleRewardRequestStep $step): void
    {
        $step->update(['heartbeat_at' => now()]);
    }

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

        $step = $step->refresh();
        $this->log('step.failed', $step->request, $step, $context);

        return $step;
    }

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

        $this->log('step.resumable', $step->request, $step->refresh());

        return true;
    }

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
        ], fn ($value): bool => ! is_null($value)));
    }
}
