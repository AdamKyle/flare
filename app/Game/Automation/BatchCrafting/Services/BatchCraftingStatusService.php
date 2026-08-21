<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingStatus;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;

class BatchCraftingStatusService
{
    /**
     * @param  BatchCraftingCapabilityService  $batchCraftingCapabilityService
     * @param  CharacterInventoryService  $characterInventoryService
     * @param  BatchCraftingSetService  $batchCraftingSetService
     * @param  CraftExperienceTargetService  $craftExperienceTargetService
     */
    public function __construct(
        private readonly BatchCraftingCapabilityService $batchCraftingCapabilityService,
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly BatchCraftingSetService $batchCraftingSetService,
        private readonly CraftExperienceTargetService $craftExperienceTargetService,
    ) {}

    /**
     * Build the complete Batch Crafting status snapshot, including full chart history and capabilities.
     *
     * @param  Character  $character  The character requesting status.
     * @return array The complete Batch Crafting status snapshot.
     */
    public function build(Character $character): array
    {
        return $this->buildSnapshot($character, includeCapabilities: true);
    }

    /**
     * Build the Batch Crafting status snapshot for a websocket broadcast.
     *
     * Carries an empty chart-point history and does not recalculate the general setup
     * capabilities, since a runtime broadcast always belongs to an already-visible batch.
     *
     * @param  Character  $character  The character the broadcast belongs to.
     * @return array The Batch Crafting runtime status snapshot.
     */
    public function buildForBroadcast(Character $character): array
    {
        $snapshot = $this->buildSnapshot($character, includeCapabilities: false);

        if (is_null($snapshot['batch'])) {
            return $snapshot;
        }

        $snapshot['batch']['chart_points'] = [];

        return $snapshot;
    }

    /**
     * Build the shared Batch Crafting status snapshot for the character.
     *
     * @param  Character  $character  The character requesting status.
     * @param  bool  $includeCapabilities  Whether to recalculate the general setup capabilities.
     * @return array The Batch Crafting status snapshot.
     */
    private function buildSnapshot(Character $character, bool $includeCapabilities): array
    {
        $batchCrafting = $this->resolveVisibleBatchCrafting($character);
        $showInfo = ! $this->hasAcknowledgedInfo($character);
        $capabilities = $this->resolveCapabilities($character, $batchCrafting, $includeCapabilities);

        if (is_null($batchCrafting)) {
            return [
                'active' => false,
                'is_running' => false,
                'is_scheduled' => false,
                'is_processing' => false,
                'is_waiting' => false,
                'is_visible' => false,
                'can_cancel' => false,
                'can_dismiss' => false,
                'show_info' => $showInfo,
                'capabilities' => $capabilities,
                'batch' => null,
            ];
        }

        $isRunning = $batchCrafting->isRunning();
        $progress = $batchCrafting->progress;
        $processingStartedAt = $progress['processing_started_at'];
        $nextAttemptAt = $progress['next_attempt_at'] ?? null;

        return [
            'active' => $isRunning,
            'is_running' => $isRunning,
            'is_scheduled' => $isRunning && is_null($processingStartedAt),
            'is_processing' => $isRunning && ! is_null($processingStartedAt) && is_null($nextAttemptAt),
            'is_waiting' => $isRunning && ! is_null($processingStartedAt) && ! is_null($nextAttemptAt),
            'is_visible' => true,
            'can_cancel' => $isRunning,
            'can_dismiss' => ! $isRunning,
            'show_info' => $showInfo,
            'capabilities' => $capabilities,
            'batch' => $this->buildBatch($character, $batchCrafting),
        ];
    }

    /**
     * Resolve the general setup capabilities, only when required by the caller or by an empty batch state.
     *
     * A runtime broadcast for an already-visible batch does not need setup capabilities, so it
     * omits them entirely (`null`) instead of recalculating Experience/Event capability work or
     * sending deliberately false placeholder facts. The frontend status provider retains the
     * last authoritative capabilities it received until a later status genuinely recalculates
     * them (initial/reopen GET, or the no-visible-batch state reached after completion/dismissal).
     *
     * @param  Character  $character  The character requesting status.
     * @param  BatchCrafting|null  $batchCrafting  The character's visible Batch Crafting record, if any.
     * @param  bool  $includeCapabilities  Whether the caller explicitly requested capability recalculation.
     * @return array|null The authoritative general setup capability facts, or null for a runtime broadcast.
     */
    private function resolveCapabilities(Character $character, ?BatchCrafting $batchCrafting, bool $includeCapabilities): ?array
    {
        if (! $includeCapabilities && ! is_null($batchCrafting)) {
            return null;
        }

        return $this->batchCraftingCapabilityService->build($character);
    }

    /**
     * Build the nested player-facing batch payload for a visible Batch Crafting record.
     *
     * @param  Character  $character  The character the batch belongs to.
     * @param  BatchCrafting  $batchCrafting  The visible Batch Crafting record.
     * @return array The nested batch status payload.
     */
    private function buildBatch(Character $character, BatchCrafting $batchCrafting): array
    {
        $progress = $batchCrafting->progress;
        $mode = CraftingBatchMode::from($progress['craft_mode']);
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
        $current = $this->resolveCurrentItem($mode, $progress);
        $destination = $this->resolveDestination($character, $disposition, $mode, $progress);
        $amount = $this->resolveAmountFields($mode, $progress);

        return [
            'id' => $batchCrafting->id,
            'batch_type' => $batchCrafting->batch_type,
            'craft_mode' => $progress['craft_mode'],
            'disposition' => $batchCrafting->disposition,
            'status' => $batchCrafting->status,
            'ended_reason' => $batchCrafting->ended_reason,
            'current_item_id' => $current['id'],
            'current_item_name' => $current['name'],
            'current_crafting_type' => $current['crafting_type'],
            'output_destination' => $progress['output_destination'] ?? null,
            'destination_set_id' => $destination['destination_set_id'],
            'destination_set_name' => $destination['destination_set_name'],
            'requested_amount' => $amount['requested'],
            'completed_amount' => $amount['completed'],
            'remaining_amount' => $amount['remaining'],
            'crafted_count' => $batchCrafting->crafted_count,
            'kept_count' => $batchCrafting->kept_count,
            'sold_count' => $batchCrafting->sold_count,
            'destroyed_count' => $batchCrafting->destroyed_count,
            'failed_count' => $batchCrafting->failed_count,
            'skipped_count' => $batchCrafting->skipped_count,
            'gold_spent' => $progress['gold_spent_total'],
            'gold_gained' => $progress['gold_gained_total'],
            'gold_left' => $character->gold,
            'started_at' => $batchCrafting->started_at->toJSON(),
            'scheduled_for' => $progress['scheduled_for'],
            'processing_started_at' => $progress['processing_started_at'],
            'next_attempt_at' => $progress['next_attempt_at'] ?? null,
            'completed_at' => $batchCrafting->completed_at?->toJSON(),
            'destination_capacity' => $destination['capacity'],
            'chart_points' => $progress['chart_points'],
            'set_progress' => $mode === CraftingBatchMode::SET ? $this->buildSetProgress($progress) : null,
            'experience_progress' => $mode === CraftingBatchMode::EXPERIENCE ? $this->buildExperienceProgress($character, $progress) : null,
            'event_progress' => $mode === CraftingBatchMode::EVENT ? $this->buildEventProgress($character, $batchCrafting, $progress) : null,
        ];
    }

    /**
     * Resolve the current item facts for the batch's craft mode.
     *
     * @param  CraftingBatchMode  $mode  The batch's craft mode.
     * @param  array  $progress  The persisted Batch Crafting progress data.
     * @return array{id: int|null, name: string|null, crafting_type: string|null} The current item facts.
     */
    private function resolveCurrentItem(CraftingBatchMode $mode, array $progress): array
    {
        if ($mode === CraftingBatchMode::AMOUNT) {
            $item = Item::find($progress['specific_item_id']);

            return ['id' => $progress['specific_item_id'], 'name' => $item?->affix_name ?? $item?->name, 'crafting_type' => $progress['specific_crafting_type']];
        }

        return [
            'id' => $progress['current_item_id'] ?? null,
            'name' => $progress['current_item_name'] ?? null,
            'crafting_type' => $progress['current_crafting_type'] ?? null,
        ];
    }

    /**
     * Resolve the Amount-specific requested/completed/remaining fields, nullable for other modes.
     *
     * @param  CraftingBatchMode  $mode  The batch's craft mode.
     * @param  array  $progress  The persisted Batch Crafting progress data.
     * @return array{requested: int|null, completed: int|null, remaining: int|null} The Amount fields.
     */
    private function resolveAmountFields(CraftingBatchMode $mode, array $progress): array
    {
        if ($mode !== CraftingBatchMode::AMOUNT) {
            return ['requested' => null, 'completed' => null, 'remaining' => null];
        }

        $requested = $progress['craft_amount'];
        $completed = $progress['craft_specific_count'];

        return ['requested' => $requested, 'completed' => $completed, 'remaining' => max(0, $requested - $completed)];
    }

    /**
     * Build the factual Craft Set progress section.
     *
     * @param  array  $progress  The persisted Batch Crafting progress data.
     * @return array The Craft Set progress facts.
     */
    private function buildSetProgress(array $progress): array
    {
        $queue = $progress['set_queue'];
        $index = $progress['set_index'];

        return [
            'total_entries' => count($queue),
            'completed_entries' => $index,
            'remaining_entries' => max(0, count($queue) - $index),
            'current_position' => $queue[$index]['position'] ?? null,
        ];
    }

    /**
     * Build the factual Craft For Experience progress section.
     *
     * @param  Character  $character  The character the batch belongs to.
     * @param  array  $progress  The persisted Batch Crafting progress data.
     * @return array The Craft For Experience progress facts.
     */
    private function buildExperienceProgress(Character $character, array $progress): array
    {
        return [
            'actions_per_minute' => CraftingBatchMode::EXPERIENCE->executionWindowSize(),
            'current_cycle_position' => $progress['cycle_position'],
            'cycle_size' => $this->craftExperienceTargetService->cycleSize(),
            'current_crafting_type' => $progress['current_crafting_type'] ?? null,
            'crafting_xp_gained' => $progress['crafting_xp_gained'],
            'crafting_skills' => $this->batchCraftingCapabilityService->craftingSkillFacts($character),
        ];
    }

    /**
     * Build the factual Craft For Event progress section.
     *
     * Resolves Event goal facts by the batch's own persisted goal id rather than
     * recalculating live Event eligibility, so a runtime broadcast never repeats
     * that setup-only capability work.
     *
     * @param  Character  $character  The character the batch belongs to.
     * @param  BatchCrafting  $batchCrafting  The visible Batch Crafting record.
     * @param  array  $progress  The persisted Batch Crafting progress data.
     * @return array The Craft For Event progress facts.
     */
    private function buildEventProgress(Character $character, BatchCrafting $batchCrafting, array $progress): array
    {
        $goal = $this->resolveFinalEventGoalFacts($character, $progress);

        return [
            'actions_per_minute' => CraftingBatchMode::EVENT->executionWindowSize(),
            'current_crafting_type' => $progress['current_crafting_type'] ?? null,
            'skipped_count' => $batchCrafting->skipped_count,
            'crafting_xp_gained' => $progress['crafting_xp_gained'],
            'goal_id' => $goal['goal_id'] ?? null,
            'max_crafts' => $goal['max_crafts'] ?? null,
            'total_crafts' => $goal['total_crafts'] ?? null,
            'next_reward_at' => $goal['next_reward_at'] ?? null,
            'reward_every' => $goal['reward_every'] ?? null,
            'character_contribution' => $goal['character_contribution'] ?? null,
        ];
    }

    /**
     * Resolve the Craft Event goal/contribution facts by the batch's own authoritative persisted goal id.
     *
     * Remains factual and inspectable for both an active run and a finished run, since it never
     * depends on the goal's current live eligibility (which changes once completed, or once the
     * Event steps away from Crafting).
     *
     * @param  Character  $character  The character the batch belongs to.
     * @param  array  $progress  The persisted Batch Crafting progress data.
     * @return array|null The goal/contribution facts, or null when no goal was ever persisted.
     */
    private function resolveFinalEventGoalFacts(Character $character, array $progress): ?array
    {
        $goalId = $progress['event_goal_id'] ?? null;

        if (is_null($goalId)) {
            return null;
        }

        return $this->batchCraftingCapabilityService->eventGoalFactsById($character, $goalId);
    }

    /**
     * Resolve the retained-item destination facts for the batch's disposition and destination.
     *
     * @param  Character  $character  The character the batch belongs to.
     * @param  BatchCraftingDisposition  $disposition  The batch's configured disposition.
     * @param  CraftingBatchMode  $mode  The batch's craft mode.
     * @param  array  $progress  The persisted Batch Crafting progress data.
     * @return array{capacity: array{current: int, max: int, remaining: int}|null, destination_set_id: int|null, destination_set_name: string|null} The resolved destination facts.
     */
    private function resolveDestination(Character $character, BatchCraftingDisposition $disposition, CraftingBatchMode $mode, array $progress): array
    {
        $empty = ['capacity' => null, 'destination_set_id' => null, 'destination_set_name' => null];

        if ($mode === CraftingBatchMode::EXPERIENCE || $mode === CraftingBatchMode::EVENT) {
            return $empty;
        }

        if ($disposition !== BatchCraftingDisposition::KEEP) {
            return $empty;
        }

        $destination = BatchCraftingOutputDestination::from($progress['output_destination']);

        return match ($destination) {
            BatchCraftingOutputDestination::INVENTORY => $this->resolveInventoryDestination($character),
            BatchCraftingOutputDestination::CRAFTED_ITEMS_SET => $this->resolveCraftedItemsSetDestination($character),
            BatchCraftingOutputDestination::INVENTORY_SET => $this->resolveInventorySetDestination($character, $progress),
        };
    }

    /**
     * Resolve the Backpack destination capacity facts for a retained Inventory batch.
     *
     * @param  Character  $character  The character the batch belongs to.
     * @return array{capacity: array{current: int, max: int, remaining: int}|null, destination_set_id: int|null, destination_set_name: string|null} The resolved Inventory destination facts.
     */
    private function resolveInventoryDestination(Character $character): array
    {
        $current = $character->getInventoryCount();
        $max = $character->inventory_max;

        return [
            'capacity' => ['current' => $current, 'max' => $max, 'remaining' => max(0, $max - $current)],
            'destination_set_id' => null,
            'destination_set_name' => null,
        ];
    }

    /**
     * Resolve the existing Crafted Items Set destination facts without creating a set.
     *
     * @param  Character  $character  The character the batch belongs to.
     * @return array{capacity: array{current: int, max: int, remaining: int}|null, destination_set_id: int|null, destination_set_name: string|null} The resolved Crafted Items Set facts.
     */
    private function resolveCraftedItemsSetDestination(Character $character): array
    {
        $set = $this->batchCraftingSetService->findBatchCraftingSet($character);

        if (is_null($set)) {
            return ['capacity' => null, 'destination_set_id' => null, 'destination_set_name' => null];
        }

        return [
            'capacity' => [
                'current' => $set->currentSlotCount(),
                'max' => $set->max_slots,
                'remaining' => $set->remainingSlots(),
            ],
            'destination_set_id' => $set->id,
            'destination_set_name' => $set->name,
        ];
    }

    /**
     * Resolve the selected normal Inventory Set destination facts for a Craft Set batch.
     *
     * @param  Character  $character  The character the batch belongs to.
     * @param  array  $progress  The persisted Batch Crafting progress data.
     * @return array{capacity: array{current: int, max: int, remaining: int}|null, destination_set_id: int|null, destination_set_name: string|null} The resolved Inventory Set facts.
     */
    private function resolveInventorySetDestination(Character $character, array $progress): array
    {
        $setId = $progress['output_set_id'] ?? null;
        $set = is_null($setId) ? null : $this->characterInventoryService->setCharacter($character)->findOwnedInventorySet($setId);

        if (is_null($set)) {
            return ['capacity' => null, 'destination_set_id' => null, 'destination_set_name' => null];
        }

        $capacity = is_null($set->max_slots) ? null : [
            'current' => $set->currentSlotCount(),
            'max' => $set->max_slots,
            'remaining' => $set->remainingSlots(),
        ];

        return [
            'capacity' => $capacity,
            'destination_set_id' => $set->id,
            'destination_set_name' => $set->name,
        ];
    }

    /**
     * Return the character's currently visible Batch Crafting record, if any.
     *
     * @param  Character  $character  The character being checked.
     * @return BatchCrafting|null The visible record, or null when nothing is visible.
     */
    private function resolveVisibleBatchCrafting(Character $character): ?BatchCrafting
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
}
