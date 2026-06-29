<?php

namespace App\Game\BatchCrafting\Services;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use Illuminate\Support\Facades\Log;
use Throwable;

class BatchCraftingLogger
{
    public function batchStarted(BatchCrafting $batchCrafting, Character $character): void
    {
        $this->info('Batch crafting started.', $this->context($batchCrafting, [
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'disposition' => $batchCrafting->disposition,
        ]));
    }

    public function actionAttempted(BatchCrafting $batchCrafting, array $action): void
    {
        $this->info('Batch crafting action attempted.', $this->context($batchCrafting, $this->actionContext($action, 'attempted')));
    }

    public function actionSucceeded(BatchCrafting $batchCrafting, array $action): void
    {
        $this->info('Batch crafting action succeeded.', $this->context($batchCrafting, $this->actionContext($action, 'succeeded')));
    }

    public function failedRoll(BatchCrafting $batchCrafting, array $action): void
    {
        $this->info('Batch crafting action failed roll.', $this->context($batchCrafting, $this->actionContext($action, 'failed')));
    }

    public function skippedAction(BatchCrafting $batchCrafting, array $action): void
    {
        $this->info('Batch crafting action skipped.', $this->context($batchCrafting, $this->actionContext($action, 'skipped')));
    }

    public function hardStop(BatchCrafting $batchCrafting, BatchCraftingEndReason $reason): void
    {
        $this->info('Batch crafting hard stop.', $this->context($batchCrafting, [
            'status' => 'stopped',
            'ended_reason' => $reason->value,
        ]));
    }

    public function batchCancelled(BatchCrafting $batchCrafting): void
    {
        $this->info('Batch crafting cancelled.', $this->context($batchCrafting, [
            'status' => 'cancelled',
            'ended_reason' => 'cancelled',
        ]));
    }

    public function batchCompleted(BatchCrafting $batchCrafting, BatchCraftingEndReason $reason): void
    {
        $this->info('Batch crafting completed.', $this->context($batchCrafting, [
            'status' => 'completed',
            'ended_reason' => $reason->value,
            'crafted_count' => $batchCrafting->crafted_count,
            'failed_count' => $batchCrafting->failed_count,
        ]));
    }

    public function exceptionCaught(BatchCrafting $batchCrafting, Throwable $throwable): void
    {
        $this->error('Batch crafting exception caught.', $this->context($batchCrafting, [
            'status' => 'failed',
            'exception_class' => get_class($throwable),
            'exception_message' => $throwable->getMessage(),
        ]));
    }

    public function dispositionApplied(BatchCrafting $batchCrafting, array $action): void
    {
        $this->info('Batch crafting disposition applied.', $this->context($batchCrafting, $this->actionContext($action, 'disposition_applied')));
    }

    private function info(string $message, array $context): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        Log::channel('batch_crafting')->info($message, $context);
    }

    private function error(string $message, array $context): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        Log::channel('batch_crafting')->error($message, $context);
    }

    private function context(BatchCrafting $batchCrafting, array $context = []): array
    {
        $progress = $batchCrafting->progress ?? [];

        return array_merge([
            'batch_crafting_id' => $batchCrafting->id,
            'character_id' => $batchCrafting->character_id,
            'user_id' => $batchCrafting->user_id,
            'batch_type' => $batchCrafting->batch_type,
            'mode' => $progress['craft_mode'] ?? $progress['alchemy_mode'] ?? $progress['trinketry_mode'] ?? null,
            'phase' => $progress['craft_enchant_phase'] ?? null,
        ], $context);
    }

    private function actionContext(array $action, string $status): array
    {
        return [
            'action' => $action['action'] ?? 'unknown',
            'status' => $action['status'] ?? $status,
            'failure' => $action['failure'] ?? null,
            'item_id' => $this->itemId($action),
            'slot_id' => $this->slotId($action),
            'currency' => $action['currency'] ?? null,
        ];
    }

    private function itemId(array $action): ?int
    {
        foreach (['crafted_item', 'enchanted_item', 'alchemy_item', 'trinketry_item', 'kept_item', 'sold_item', 'destroyed_item', 'listed_item', 'disenchanted_item'] as $key) {
            if (isset($action[$key]['item_id'])) {
                return (int) $action[$key]['item_id'];
            }
        }

        return null;
    }

    private function slotId(array $action): ?int
    {
        foreach (['crafted_item', 'enchanted_item', 'kept_item', 'sold_item', 'destroyed_item', 'listed_item', 'disenchanted_item'] as $key) {
            if (isset($action[$key]['slot_id'])) {
                return (int) $action[$key]['slot_id'];
            }
        }

        return null;
    }
}
