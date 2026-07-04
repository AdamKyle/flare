<?php

namespace App\Game\BatchCrafting\Services;

use App\Admin\Events\BatchCraftingMonitoringUpdated;
use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\AutomationType;
use App\Game\BatchCrafting\Events\BatchCraftingStatusUpdated;
use App\Game\BatchCrafting\Jobs\BatchCraftingJob;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\NpcActions\WorkBench\Services\HolyItemService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BatchCraftingService
{
    public const DURATION_HOURS = 8;

    public const SETS_PER_RECURRING_TICK = 6;

    public const ITEMS_PER_RECURRING_TICK = 6;

    public const RECURRING_DELAY_SECONDS = 60;

    public const IMMEDIATE_DELAY_SECONDS = 2;

    private readonly GlobalEventGoalEligibilityService $globalEventGoalEligibilityService;

    public function __construct(
        private readonly BatchCraftingProcessor $processor,
        private readonly CraftingService $craftingService,
        private readonly BatchCraftingLogger $batchCraftingLogger,
        private readonly EnchantingService $enchantingService,
        private readonly BatchCraftingSetService $batchCraftingSetService,
        private readonly HolyItemService $holyItemService,
        ?GlobalEventGoalEligibilityService $globalEventGoalEligibilityService = null,
    ) {
        $this->globalEventGoalEligibilityService = $globalEventGoalEligibilityService ?? new GlobalEventGoalEligibilityService();
    }

    public function start(Character $character, array $data): BatchCrafting
    {
        $type = BatchCraftingType::from($data['batch_type']);
        $disposition = BatchCraftingDisposition::from($data['disposition']);

        if (! $disposition->isAllowedFor($type)) {
            throw ValidationException::withMessages([
                'disposition' => 'This disposition is not allowed for this batch crafting type.',
            ]);
        }

        if ($this->active($character)) {
            throw ValidationException::withMessages([
                'batch_crafting' => 'Batch crafting is already running for this character.',
            ]);
        }

        if ($character->currentAutomations()->where('type', AutomationType::FACTION_LOYALTY)->exists()) {
            throw ValidationException::withMessages([
                'batch_crafting' => 'Batch crafting cannot start while faction loyalty automation is running.',
            ]);
        }

        if ($character->is_dead) {
            throw ValidationException::withMessages([
                'batch_crafting' => 'Batch crafting cannot start while the character is dead.',
            ]);
        }

        if ($this->isInventoryFull($character)) {
            throw ValidationException::withMessages([
                'batch_crafting' => 'Batch crafting cannot start without inventory space.',
            ]);
        }

        if ($this->currencyAmount($character, $type->requiredCurrency()) <= 0) {
            throw ValidationException::withMessages([
                'batch_crafting' => 'Batch crafting cannot start without the required currency.',
            ]);
        }

        if ($type === BatchCraftingType::HOLY_OILS) {
            $this->validateHolyOilSelections($character, $data);
        }

        if ($this->isSetMode($type, $data['progress'] ?? []) && $disposition !== BatchCraftingDisposition::KEEP) {
            throw ValidationException::withMessages([
                'disposition' => 'Set-based batch crafting only supports the Keep disposition.',
            ]);
        }

        $progress = $this->validatedProgress($character, $type, $data);
        $progress['tick_delay_seconds'] = $this->tickDelaySeconds($type, $progress);

        $batchCrafting = BatchCrafting::create([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => $type->value,
            'disposition' => $disposition->value,
            'started_at' => now(),
            'ends_at' => now()->addHours(self::DURATION_HOURS),
            'status' => 'running',
            'progress' => $progress,
            'selected_items' => $data['selected_items'] ?? [],
            'selected_oils' => $data['selected_oils'] ?? [],
        ]);

        $this->logger()->batchStarted($batchCrafting, $character);

        event(new ServerMessageEvent($character->user, 'Batch crafting has started. Type: ' . $type->label()));
        event(new BatchCraftingStatusUpdated($character->user_id));
        event(new BatchCraftingMonitoringUpdated($character->id));

        if (! app()->runningUnitTests()) {
            BatchCraftingJob::dispatch($batchCrafting->id)->delay(now()->addSeconds($progress['tick_delay_seconds']))->onConnection('long_running')->onQueue('default_long');
        }

        return $batchCrafting;
    }

    public function preview(Character $character, array $data): array
    {
        $type = BatchCraftingType::from($data['batch_type']);
        $progress = $data['progress'] ?? [];
        $selectedItemIds = $data['selected_items'] ?? [];
        $selectedOilIds = $data['selected_oils'] ?? [];

        return [
            'amount_preview' => $this->amountPreview($character, $type, $progress),
            'alchemy_amount_preview' => $this->alchemyAmountPreview($character, $type, $progress),
            'holy_oil_selected_preview' => $this->holyOilsSelectedPreview($character, $type, $selectedItemIds, $selectedOilIds, $progress),
            'holy_oil_set_preview' => $this->holyOilsSetPreview($character, $type, $selectedOilIds, $progress),
        ];
    }

    public function active(Character $character): ?BatchCrafting
    {
        return BatchCrafting::where('character_id', $character->id)
            ->whereNull('completed_at')
            ->whereNull('cancelled_at')
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();
    }

    public function visible(Character $character): ?BatchCrafting
    {
        return BatchCrafting::where('character_id', $character->id)
            ->where(function ($query) {
                $query->whereNull('completed_at')
                    ->whereNull('cancelled_at')
                    ->orWhereNull('panel_dismissed_at');
            })
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();
    }

    public function status(Character $character): array
    {
        $batchCrafting = $this->visible($character);

        if (is_null($batchCrafting)) {
            return [
                'active' => false,
                'is_running' => false,
                'is_visible' => false,
                'can_cancel' => false,
                'can_dismiss' => false,
                'completed' => false,
                'status' => 'idle',
                'show_info' => ! BatchCrafting::where('character_id', $character->id)->where('info_acknowledged', true)->exists(),
                'event_batch' => $this->eventBatchData($character),
                'craft_experience_options' => $this->craftExperienceOptions($character)->all(),
            ];
        }

        $type = BatchCraftingType::from($batchCrafting->batch_type);
        $timer = $this->timerDetails($batchCrafting);
        $actionLog = $batchCrafting->action_log ?? [];
        $progress = $batchCrafting->progress ?? [];
        $outcomeTotals = $progress['outcome_totals'] ?? [];
        $currentItemSnapshot = $this->currentItemSnapshot($batchCrafting);
        $batchCraftingSet = InventorySet::query()
            ->where('character_id', $character->id)
            ->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)
            ->first();
        $progressPercent = $this->progressPercent($batchCrafting, $timer['progress_percent']);

        return [
            'active' => $batchCrafting->isRunning(),
            'is_running' => $batchCrafting->isRunning(),
            'is_visible' => true,
            'can_cancel' => $batchCrafting->isRunning(),
            'can_dismiss' => ! $batchCrafting->isRunning(),
            'completed' => ! $batchCrafting->isRunning(),
            'status' => $batchCrafting->isRunning() ? 'running' : ($batchCrafting->status ?? 'completed'),
            'show_info' => ! BatchCrafting::where('character_id', $character->id)->where('info_acknowledged', true)->exists(),
            'event_batch' => $this->eventBatchData($character, $batchCrafting),
            'craft_experience_options' => $this->craftExperienceOptions($character)->all(),
            'batch' => [
                'id' => $batchCrafting->id,
                'type' => $batchCrafting->batch_type,
                'batch_type' => $batchCrafting->batch_type,
                'batch_label' => $type->label(),
                'human_mode_label' => $this->humanModeLabel($batchCrafting),
                'disposition' => $batchCrafting->disposition,
                'started_at' => $batchCrafting->started_at?->toJSON(),
                'ends_at' => $batchCrafting->ends_at?->toJSON(),
                'ended_at' => $batchCrafting->completed_at?->toJSON(),
                'completed_at' => $batchCrafting->completed_at?->toJSON(),
                'elapsed_seconds' => $timer['elapsed_seconds'],
                'remaining_seconds' => $timer['remaining_seconds'],
                'elapsed_human' => $timer['elapsed_human'],
                'remaining_human' => $timer['remaining_human'],
                'progress_percent' => $progressPercent,
                'stop_reason' => $batchCrafting->ended_reason,
                'ended_reason' => $batchCrafting->ended_reason,
                'status' => $batchCrafting->status,
                'inventory_count' => $character->getInventoryCount(),
                'inventory_max' => $character->inventory_max,
                'inventory_remaining' => max(0, $character->inventory_max - $character->getInventoryCount()),
                'inventory_percent' => $character->inventory_max > 0
                    ? min(100, (int) floor(($character->getInventoryCount() / $character->inventory_max) * 100))
                    : 0,
                'alchemy_bag_count' => $character->getAlchemyBagCount(),
                'alchemy_bag_max' => $character->alchemy_bag_limit,
                'alchemy_bag_remaining' => max(0, $character->alchemy_bag_limit - $character->getAlchemyBagCount()),
                'mode' => $progress['craft_mode'] ?? $progress['alchemy_mode'] ?? $progress['trinketry_mode'] ?? $progress['enchant_mode'] ?? $progress['holy_oil_mode'] ?? null,
                'phase' => $progress['craft_enchant_phase'] ?? null,
                'next_action' => $this->nextAction($batchCrafting),
                'last_action' => $this->lastAction($actionLog),
                'event_mode' => (bool) ($progress['event_mode'] ?? false),
                'event_action' => $progress['event_action'] ?? null,
                'event_type' => $progress['event_type'] ?? null,
                'event_step' => $progress['event_step'] ?? null,
                'event_actions_per_tick' => $progress['event_actions_per_tick'] ?? null,
                'event_goal_progress' => $this->eventGoalProgress($progress),
                'event_character_contribution' => $this->eventCharacterContribution($character, $progress),
                'event_enchant_phase' => $progress['event_enchant_phase'] ?? null,
                'event_fallback_phase' => $progress['event_fallback_phase'] ?? null,
                'event_current_phase_label' => $this->eventCurrentPhaseLabel($batchCrafting),
                'event_stop_reason' => $this->eventStopReason($batchCrafting),
                'event_fallback_crafted_this_tick' => $progress['event_fallback_crafted_this_tick'] ?? 0,
                'event_fallback_enchanted_this_tick' => $progress['event_fallback_enchanted_this_tick'] ?? 0,
                'event_crafting_xp_gained' => (int) ($progress['event_crafting_xp_gained'] ?? 0),
                'event_enchanting_xp_gained' => (int) ($progress['event_enchanting_xp_gained'] ?? 0),
                'requested_amount' => $this->requestedAmount($progress),
                'completed_amount' => $this->completedAmount($progress),
                'remaining_amount' => $this->remainingAmount($progress),
                'completion_summary' => $this->craftCompletionSummary($type, $progress),
                'selected_set' => $this->selectedSetSummary($progress),
                'chart_points' => $progress['chart_points'] ?? ['currency' => [], 'outcomes' => [], 'gold_dust' => []],
                'current_item_name' => $currentItemSnapshot['name'] ?? null,
                'current_item_snapshot' => $currentItemSnapshot,
                'current_crafted_item_snapshot' => $this->latestActionSnapshot($actionLog, 'crafted_item'),
                'current_enchanted_item_snapshot' => $this->latestActionSnapshot($actionLog, 'enchanted_item'),
                'alchemy_current_item' => $this->alchemyCurrentItemSnapshot($progress, $actionLog),
                'trinketry_current_item' => $this->latestActionSnapshot($actionLog, 'trinketry_item'),
                'crafted_item_snapshots' => $this->snapshotList($progress, 'crafted_item_snapshots', $actionLog, 'crafted_item'),
                'enchanted_item_snapshots' => $this->snapshotList($progress, 'enchanted_item_snapshots', $actionLog, 'enchanted_item'),
                'alchemy_item_snapshots' => $this->snapshotList($progress, 'alchemy_item_snapshots', $actionLog, 'alchemy_item'),
                'trinketry_item_snapshots' => $this->snapshotList($progress, 'trinketry_item_snapshots', $actionLog, 'trinketry_item'),
                'holy_oil_target_item_snapshots' => $this->holyOilTargetSnapshots($progress, $actionLog),
                'gold_spent' => $this->goldSpent($actionLog, $batchCrafting),
                'gold_spent_total' => (int) ($progress['currency_totals']['gold_spent'] ?? 0),
                'gold_gained_total' => (int) ($progress['currency_totals']['gold_gained'] ?? 0),
                'gold_left' => (int) $character->gold,
                'gold_dust_spent_total' => (int) ($progress['currency_totals']['gold_dust_spent'] ?? 0),
                'gold_dust_gained_total' => (int) ($progress['currency_totals']['gold_dust_gained'] ?? 0),
                'gold_dust_left' => (int) $character->gold_dust,
                'no_inventory_reason' => $progress['no_inventory_reason'] ?? null,
                'skills' => array_merge(
                    $this->craftingSkillData($character, $type),
                    $this->disenchantingSkillProgressIfApplicable($character, $type, $batchCrafting->disposition)
                ),
                'skills_being_trained' => $this->skillsBeingTrained($character, $batchCrafting),
                'currency' => [
                    'type' => $type->requiredCurrency(),
                    'amount' => $this->currencyAmount($character, $type->requiredCurrency()),
                ],
                'counts' => [
                    'crafted' => $batchCrafting->crafted_count,
                    'sold' => $batchCrafting->sold_count,
                    'destroyed' => $batchCrafting->destroyed_count,
                    'listed' => $batchCrafting->listed_count,
                    'disenchanted' => (int) ($outcomeTotals['disenchanted'] ?? $this->countActionStatus($actionLog, 'disenchanted')),
                    'enchanted' => (int) ($outcomeTotals['enchanted'] ?? $progress['enchant_set_completed'] ?? $this->countActionStatus($actionLog, 'enchanted')),
                    'alchemy_processed' => (int) ($outcomeTotals['alchemy_processed'] ?? $this->countActionKey($actionLog, 'alchemy_item')),
                    'trinketry_processed' => (int) ($outcomeTotals['trinketry_processed'] ?? $this->countActionKey($actionLog, 'trinketry_item')),
                    'kept' => $batchCrafting->kept_count,
                    'applied' => $batchCrafting->applied_count,
                    'skipped' => $batchCrafting->skipped_count,
                    'failed' => $batchCrafting->failed_count,
                ],
                'craft_set_current_item' => $progress['craft_set_current_item'] ?? null,
                'craft_enchant_set_phase' => $progress['craft_enchant_set_phase'] ?? null,
                'craft_enchant_set_requested' => $progress['craft_enchant_set_requested'] ?? null,
                'craft_enchant_set_prefix_applied_count' => $progress['craft_enchant_set_prefix_applied_count'] ?? null,
                'craft_enchant_set_suffix_applied_count' => $progress['craft_enchant_set_suffix_applied_count'] ?? null,
                'craft_enchant_set_completed_final_count' => $progress['craft_enchant_set_completed_final_count'] ?? null,
                'craft_enchant_set_current_item' => $progress['craft_enchant_set_current_item'] ?? null,
                'craft_enchant_set_current_prefix' => $progress['craft_enchant_set_current_prefix'] ?? null,
                'craft_enchant_set_current_suffix' => $progress['craft_enchant_set_current_suffix'] ?? null,
                'enchant_set_total' => $progress['enchant_set_total'] ?? null,
                'enchant_set_completed' => $progress['enchant_set_completed'] ?? null,
                'enchant_set_skipped' => $progress['enchant_set_skipped'] ?? null,
                'enchant_set_current_item' => $progress['enchant_set_current_item'] ?? null,
                'enchant_affix_ids' => $progress['enchant_affix_ids'] ?? null,
                'enchant_affix_names' => $this->enchantAffixNames($progress),
                'holy_oil_eligible_items' => $progress['holy_oil_eligible_items'] ?? null,
                'holy_oil_total_stacks' => $progress['holy_oil_total_stacks'] ?? null,
                'holy_oil_requested_applications' => $progress['holy_oil_requested_applications'] ?? null,
                'holy_oil_completed_applications' => $progress['holy_oil_completed_applications'] ?? null,
                'holy_oil_remaining_applications' => $this->holyOilRemainingApplications($progress),
                'holy_oil_total_stat_bonus_applied' => (float) ($progress['holy_oil_total_stat_bonus_applied'] ?? 0),
                'holy_oil_total_devouring_darkness_bonus_applied' => (float) ($progress['holy_oil_total_devouring_darkness_bonus_applied'] ?? 0),
                'holy_oil_gold_dust_spent' => (int) ($progress['holy_oil_gold_dust_spent'] ?? 0),
                'holy_oil_skipped_items' => $batchCrafting->skipped_count,
                'holy_oil_selected_item_count' => count($batchCrafting->selected_items ?? []),
                'holy_oil_selected_oil_count' => count($batchCrafting->selected_oils ?? []),
                'holy_oil_current_target_item' => $progress['holy_oil_current_target_item'] ?? null,
                'holy_oil_current_oil_item' => $progress['holy_oil_current_oil_item'] ?? null,
                'kept_set_summary' => $this->keptSetSummary($batchCrafting),
                'amount_preview' => $this->amountPreview($character, $type, $progress),
                'alchemy_amount_preview' => $this->alchemyAmountPreview($character, $type, $progress),
                'holy_oil_selected_preview' => $this->holyOilsSelectedPreview($character, $type, $batchCrafting->selected_items ?? [], $batchCrafting->selected_oils ?? [], $progress),
                'holy_oil_set_preview' => $this->holyOilsSetPreview($character, $type, $batchCrafting->selected_oils ?? [], $progress),
                'batch_crafting_set' => [
                    'current_slots' => $batchCraftingSet?->currentSlotCount() ?? 0,
                    'max_slots' => $batchCraftingSet?->max_slots ?? InventorySet::BATCH_CRAFTING_MAX_SLOTS,
                    'remaining_slots' => $batchCraftingSet?->remainingSlots() ?? InventorySet::BATCH_CRAFTING_MAX_SLOTS,
                    'percent' => $this->craftedSetPercent($batchCraftingSet),
                ],
                'action_log' => $actionLog,
                'action_history' => $actionLog,
            ],
        ];
    }

    public function process(BatchCrafting $batchCrafting): BatchCrafting
    {
        if (! $batchCrafting->isRunning()) {
            return $batchCrafting;
        }

        $character = $batchCrafting->character()->first();

        if ($character->is_dead) {
            return $this->complete($batchCrafting, BatchCraftingEndReason::DIED);
        }

        if ($batchCrafting->ends_at <= now()) {
            return $this->complete($batchCrafting, BatchCraftingEndReason::COMPLETED_DURATION);
        }

        if ($this->isInventoryFull($character)) {
            return $this->complete($batchCrafting, BatchCraftingEndReason::NO_INVENTORY_SPACE);
        }

        $progress = $batchCrafting->progress ?? [];

        if (($progress['nothing_left'] ?? false) === true) {
            return $this->complete($batchCrafting, BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT);
        }

        if (($progress['all_oils_applied'] ?? false) === true) {
            return $this->complete($batchCrafting, BatchCraftingEndReason::ALL_OILS_APPLIED);
        }

        if (($progress['no_oils_left'] ?? false) === true) {
            return $this->complete($batchCrafting, BatchCraftingEndReason::NO_OILS_LEFT);
        }

        if (($progress['no_selected_items_left'] ?? false) === true) {
            return $this->complete($batchCrafting, BatchCraftingEndReason::NO_SELECTED_ITEMS_LEFT);
        }

        $type = BatchCraftingType::from($batchCrafting->batch_type);
        $currency = $progress['required_currency'] ?? $type->requiredCurrency();

        if ($this->currencyAmount($character, $currency) <= 0) {
            return $this->complete($batchCrafting, $this->currencyEndReason($currency));
        }

        $currencyBefore = $this->currencyAmount($character, $currency);

        try {
            $result = $this->processor->processOneTick($batchCrafting, $character);
        } catch (\Throwable $e) {
            $this->logger()->exceptionCaught($batchCrafting, $e);

            return $this->complete($batchCrafting, BatchCraftingEndReason::FAILED);
        }

        if (! isset($result['end_reason']) && $this->eventGoalCompletedAfterTick($batchCrafting->refresh())) {
            $result['end_reason'] = BatchCraftingEndReason::EVENT_GOAL_COMPLETE;
        }

        if (isset($result['end_reason'])) {
            $counts = $result['counts'] ?? [];

            foreach ($counts as $column => $increment) {
                if (! in_array($column, [
                    'crafted_count',
                    'sold_count',
                    'destroyed_count',
                    'listed_count',
                    'kept_count',
                    'applied_count',
                    'skipped_count',
                    'failed_count',
                ])) {
                    continue;
                }

                $batchCrafting->increment($column, $increment);
            }

            if (! empty($result['actions'] ?? [])) {
                $this->appendActionLog($batchCrafting, $counts, $result['actions']);
                $this->logActions($batchCrafting, $result['actions']);
            }

            $this->recordProgressTotals($batchCrafting->refresh(), $counts, $result['actions'] ?? []);
            $this->recordChartPoint($batchCrafting->refresh(), $currency, $currencyBefore, $counts, $result['actions'] ?? []);

            return $this->complete($batchCrafting, $result['end_reason']);
        }

        $counts = $result['counts'] ?? [];

        foreach ($counts as $column => $increment) {
            if (! in_array($column, [
                'crafted_count',
                'sold_count',
                'destroyed_count',
                'listed_count',
                'kept_count',
                'applied_count',
                'skipped_count',
                'failed_count',
            ])) {
                continue;
            }

            $batchCrafting->increment($column, $increment);
        }

        if (! empty($counts) || ! empty($result['actions'] ?? [])) {
            $this->appendActionLog($batchCrafting, $counts, $result['actions'] ?? []);
        }

        if (! empty($result['actions'] ?? [])) {
            $this->logActions($batchCrafting, $result['actions']);
        }

        $this->recordProgressTotals($batchCrafting->refresh(), $counts, $result['actions'] ?? []);
        $this->recordChartPoint($batchCrafting->refresh(), $currency, $currencyBefore, $counts, $result['actions'] ?? []);

        event(new BatchCraftingStatusUpdated($batchCrafting->user_id));
        event(new BatchCraftingMonitoringUpdated($batchCrafting->character_id));

        return $batchCrafting->refresh();
    }

    public function cancel(Character $character): ?BatchCrafting
    {
        $batchCrafting = $this->active($character);

        if (is_null($batchCrafting)) {
            return null;
        }

        return $this->complete($batchCrafting, BatchCraftingEndReason::CANCELLED, ['cancelled_at' => now()]);
    }

    public function dismiss(Character $character): void
    {
        $batchCrafting = $this->visible($character);

        if (! is_null($batchCrafting) && ! $batchCrafting->isRunning()) {
            $batchCrafting->update(['panel_dismissed_at' => now()]);
            event(new BatchCraftingStatusUpdated($character->user_id));
            event(new BatchCraftingMonitoringUpdated($character->id));
        }
    }

    public function acknowledgeInfo(Character $character): void
    {
        BatchCrafting::updateOrCreate([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'completed_at' => null,
            'cancelled_at' => null,
            'status' => 'info',
        ], [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'info_acknowledged' => true,
            'started_at' => now(),
            'ends_at' => now(),
            'completed_at' => now(),
            'ended_reason' => BatchCraftingEndReason::COMPLETED_DURATION->value,
            'panel_dismissed_at' => now(),
        ]);
    }

    public function completeForDeath(Character $character): void
    {
        $batchCrafting = $this->active($character);

        if (! is_null($batchCrafting)) {
            $this->complete($batchCrafting, BatchCraftingEndReason::DIED);
        }
    }

    private function complete(BatchCrafting $batchCrafting, BatchCraftingEndReason $reason, array $extra = []): BatchCrafting
    {
        $batchCrafting->update(array_merge([
            'completed_at' => now(),
            'ended_reason' => $reason->value,
            'status' => 'completed',
            'panel_dismissed_at' => null,
        ], $extra));

        if ($reason === BatchCraftingEndReason::CANCELLED) {
            $this->logger()->batchCancelled($batchCrafting);
        } elseif (in_array($reason, [
            BatchCraftingEndReason::COMPLETED_DURATION,
            BatchCraftingEndReason::AMOUNT_REACHED,
            BatchCraftingEndReason::ALL_OILS_APPLIED,
            BatchCraftingEndReason::CRAFT_SET_COMPLETE,
            BatchCraftingEndReason::ENCHANT_SET_COMPLETE,
            BatchCraftingEndReason::CRAFT_ENCHANT_SET_COMPLETE,
        ], true)) {
            $this->logger()->batchCompleted($batchCrafting, $reason);
        } else {
            $this->logger()->hardStop($batchCrafting, $reason);
        }

        $user = $batchCrafting->user()->first();

        if (! is_null($user)) {
            $reasonLabel = ucwords(str_replace('_', ' ', $reason->value));
            event(new ServerMessageEvent($user, 'Batch crafting has ended. Reason: ' . $reasonLabel));
        }

        event(new BatchCraftingStatusUpdated($batchCrafting->user_id));
        event(new BatchCraftingMonitoringUpdated($batchCrafting->character_id));

        return $batchCrafting->refresh();
    }

    private function logActions(BatchCrafting $batchCrafting, array $actions): void
    {
        foreach ($actions as $action) {
            $this->logger()->actionAttempted($batchCrafting, $action);
            $status = $this->actionStatus($action);

            if ($status === 'failed') {
                $this->logger()->failedRoll($batchCrafting, $action);
            } elseif ($status === 'skipped') {
                $this->logger()->skippedAction($batchCrafting, $action);
            } else {
                $this->logger()->actionSucceeded($batchCrafting, $action);
            }

            if (isset($action['disposition'])) {
                $this->logger()->dispositionApplied($batchCrafting, $action);
            }
        }
    }

    private function logger(): BatchCraftingLogger
    {
        return $this->batchCraftingLogger;
    }

    private function appendActionLog(BatchCrafting $batchCrafting, array $counts, array $actions = []): void
    {
        $entries = [];
        $actions = empty($actions) ? [[]] : $actions;

        foreach ($actions as $action) {
            $entry = [
                'ts' => now()->toJSON(),
                'timestamp' => now()->toJSON(),
                'type' => $batchCrafting->batch_type,
                'action_type' => $action['action'] ?? $batchCrafting->batch_type,
                'status' => $this->actionStatus($action),
            ];

            foreach ($counts as $column => $increment) {
                $entry[str_replace('_count', '', $column)] = $increment;
            }

            $entries[] = array_merge($entry, $action);
        }

        $log = $batchCrafting->action_log ?? [];
        $log = array_merge($log, $entries);

        $batchCrafting->update(['action_log' => $log]);
    }

    private function recordProgressTotals(BatchCrafting $batchCrafting, array $counts, array $actions): void
    {
        $progress = $batchCrafting->progress ?? [];
        $totals = array_merge([
            'crafted' => 0,
            'enchanted' => 0,
            'disenchanted' => 0,
            'alchemy_processed' => 0,
            'trinketry_processed' => 0,
            'applied' => 0,
            'skipped' => 0,
            'failed' => 0,
        ], $progress['outcome_totals'] ?? []);

        $totals['crafted'] += (int) ($counts['crafted_count'] ?? 0);
        $totals['enchanted'] += (int) ($counts['enchanted_count'] ?? 0);
        $totals['disenchanted'] += (int) ($counts['disenchanted_count'] ?? 0);
        $totals['applied'] += (int) ($counts['applied_count'] ?? 0);
        $totals['skipped'] += (int) ($counts['skipped_count'] ?? 0);
        $totals['failed'] += (int) ($counts['failed_count'] ?? 0);

        foreach ($actions as $action) {
            if (isset($action['alchemy_item'])) {
                $totals['alchemy_processed']++;
            }

            if (isset($action['trinketry_item'])) {
                $totals['trinketry_processed']++;
            }

            $this->appendSnapshotToProgress($progress, 'crafted_item_snapshots', $action['crafted_item'] ?? null);
            $this->appendSnapshotToProgress($progress, 'enchanted_item_snapshots', $action['enchanted_item'] ?? null);
            $this->appendSnapshotToProgress($progress, 'alchemy_item_snapshots', $action['alchemy_item'] ?? null);
            $this->appendSnapshotToProgress($progress, 'trinketry_item_snapshots', $action['trinketry_item'] ?? null);
            $this->appendSnapshotToProgress($progress, 'holy_oil_target_item_snapshots', $action['oil_application']['target_item'] ?? null);
            $progress['event_crafting_xp_gained'] = ((int) ($progress['event_crafting_xp_gained'] ?? 0)) + (int) ($action['crafting_xp_gained'] ?? 0);
            $progress['event_enchanting_xp_gained'] = ((int) ($progress['event_enchanting_xp_gained'] ?? 0)) + (int) ($action['enchanting_xp_gained'] ?? 0);
        }

        $progress['outcome_totals'] = $totals;

        $batchCrafting->update(['progress' => $progress]);
    }

    private function appendSnapshotToProgress(array &$progress, string $key, mixed $snapshot): void
    {
        if (! is_array($snapshot)) {
            return;
        }

        $progress[$key] = $progress[$key] ?? [];
        $progress[$key][] = [
            'display_name' => $snapshot['name'] ?? 'Unknown Item',
            'quantity' => 1,
            'snapshot' => $snapshot,
            'slot_id' => $snapshot['slot_id_for_modal'] ?? null,
            'crafted_at' => now()->toJSON(),
        ];
    }

    private function recordChartPoint(BatchCrafting $batchCrafting, string $currency, int $currencyBefore, array $counts, array $actions): void
    {
        $successCount = ($counts['crafted_count'] ?? 0)
            + ($counts['enchanted_count'] ?? 0)
            + ($counts['sold_count'] ?? 0)
            + ($counts['destroyed_count'] ?? 0)
            + ($counts['listed_count'] ?? 0)
            + ($counts['kept_count'] ?? 0)
            + ($counts['disenchanted_count'] ?? 0)
            + ($counts['applied_count'] ?? 0);
        $failureCount = ($counts['failed_count'] ?? 0) + ($counts['skipped_count'] ?? 0);

        $gainField = $currency === 'gold_dust' ? 'gold_dust_gained' : 'gold_gained';
        $gained = collect($actions)->sum(fn (array $action) => (int) ($action[$gainField] ?? 0));
        $goldDustGained = collect($actions)->sum(fn (array $action) => (int) ($action['gold_dust_gained'] ?? 0));

        $currencyAfter = $this->currencyAmount($batchCrafting->character()->first(), $currency);
        $spent = max(0, $currencyBefore - $currencyAfter + $gained);

        $progress = $batchCrafting->progress ?? [];
        $chartPoints = $progress['chart_points'] ?? ['currency' => [], 'outcomes' => [], 'gold_dust' => []];
        $tick = count($chartPoints['currency']) + 1;

        $chartPoints['currency'][] = ['tick' => $tick, 'spent' => $spent, 'gained' => $gained];
        $chartPoints['outcomes'][] = ['tick' => $tick, 'success' => $successCount, 'failure' => $failureCount];
        $chartPoints['gold_dust'][] = ['tick' => $tick, 'gained' => $goldDustGained];

        $progress['chart_points'] = $chartPoints;
        $progress['currency_totals'] = $this->accumulatedCurrencyTotals($progress, $currency, $spent, $actions);
        $batchCrafting->update(['progress' => $progress]);
    }

    private function accumulatedCurrencyTotals(array $progress, string $currency, int $spent, array $actions): array
    {
        $totals = $progress['currency_totals'] ?? [
            'gold_spent' => 0,
            'gold_gained' => 0,
            'gold_dust_spent' => 0,
            'gold_dust_gained' => 0,
        ];

        if ($currency === 'gold') {
            $totals['gold_spent'] += $spent;
        } elseif ($currency === 'gold_dust') {
            $totals['gold_dust_spent'] += $spent;
        }

        $totals['gold_gained'] += collect($actions)->sum(fn (array $action) => (int) ($action['gold_gained'] ?? 0));
        $totals['gold_dust_gained'] += collect($actions)->sum(fn (array $action) => (int) ($action['gold_dust_gained'] ?? 0));

        return $totals;
    }

    private function disenchantingSkillProgressIfApplicable(Character $character, BatchCraftingType $type, string $disposition): array
    {
        if ($type !== BatchCraftingType::CRAFT_AND_ENCHANT) {
            return [];
        }

        if (! in_array($disposition, [
            BatchCraftingDisposition::DISENCHANT->value,
            BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST->value,
        ], true)) {
            return [];
        }

        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::DISENCHANTING->value))
            ->with('baseSkill')
            ->first();

        if (is_null($skill) || $skill->level >= $skill->max_level) {
            return [];
        }

        $xpPercent = $skill->xp_max > 0 ? min(100, (int) floor(($skill->xp / $skill->xp_max) * 100)) : 0;

        return [[
            'key' => 'disenchanting',
            'name' => $skill->name,
            'level' => $skill->level,
            'current_xp' => $skill->xp,
            'next_level_xp' => $skill->xp_max,
            'xp_percent' => $xpPercent,
            'is_maxed' => false,
        ]];
    }

    private function enchantAffixNames(array $progress): array
    {
        $affixIds = $progress['enchant_affix_ids'] ?? [];

        if (empty($affixIds)) {
            return [];
        }

        return ItemAffix::whereIn('id', $affixIds)->pluck('name')->values()->all();
    }

    private function holyOilRemainingApplications(array $progress): ?int
    {
        $requested = $progress['holy_oil_requested_applications'] ?? null;
        $completed = $progress['holy_oil_completed_applications'] ?? null;

        if (is_null($requested) || is_null($completed)) {
            return null;
        }

        return max(0, (int) $requested - (int) $completed);
    }

    private function craftingSkillData(Character $character, BatchCraftingType $type): array
    {
        $skillNameMap = $this->relevantSkillNameMap($type);

        if (empty($skillNameMap)) {
            return [];
        }

        $skills = $character->skills()->with('baseSkill')->get();
        $result = [];

        foreach ($skillNameMap as $key => $lookup) {
            $skill = $skills->first(function ($skill) use ($lookup) {
                if ($lookup['by'] === 'name') {
                    return $skill->name === $lookup['name'];
                }

                return ($skill->baseSkill->type ?? null) === $lookup['type'];
            });

            if (is_null($skill)) {
                continue;
            }

            $maxLevel = $skill->max_level;
            $isMaxed = $skill->level >= $maxLevel;
            $xpPercent = $isMaxed ? 100 : ($skill->xp_max > 0 ? min(100, (int) floor(($skill->xp / $skill->xp_max) * 100)) : 0);

            $result[] = [
                'key' => $key,
                'name' => $skill->name,
                'level' => $skill->level,
                'current_xp' => $skill->xp,
                'next_level_xp' => $skill->xp_max,
                'xp_percent' => $xpPercent,
                'is_maxed' => $isMaxed,
            ];
        }

        return $result;
    }

    private function skillsBeingTrained(Character $character, BatchCrafting $batchCrafting): array
    {
        return $this->craftingSkillData($character, BatchCraftingType::from($batchCrafting->batch_type));
    }

    private function craftExperienceOptions(Character $character)
    {
        $skills = $character->skills()->with('baseSkill')->get();

        return collect([
            ['value' => 'weapon', 'label' => 'Weapon Crafting', 'skill_name' => 'Weapon Crafting', 'batch_type' => BatchCraftingType::CRAFT->value],
            ['value' => 'armour', 'label' => 'Armour Crafting', 'skill_name' => 'Armour Crafting', 'batch_type' => BatchCraftingType::CRAFT->value],
            ['value' => 'ring', 'label' => 'Ring Crafting', 'skill_name' => 'Ring Crafting', 'batch_type' => BatchCraftingType::CRAFT->value],
            ['value' => 'spell', 'label' => 'Spell Crafting', 'skill_name' => 'Spell Crafting', 'batch_type' => BatchCraftingType::CRAFT->value],
            ['value' => 'enchanting', 'label' => 'Enchanting', 'skill_name' => 'Enchanting', 'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value],
            ['value' => 'disenchanting', 'label' => 'Disenchanting', 'skill_name' => 'Disenchanting', 'batch_type' => null],
            ['value' => 'trinketry', 'label' => 'Trinketry', 'skill_name' => 'Trinketry', 'batch_type' => BatchCraftingType::TRINKETRY->value],
            ['value' => 'gem_crafting', 'label' => 'Gem Crafting', 'skill_name' => 'Gem Crafting', 'batch_type' => null],
        ])->map(function (array $option) use ($skills): array {
            $skill = $skills->first(fn ($characterSkill) => $characterSkill->name === $option['skill_name'] || $characterSkill->baseSkill?->name === $option['skill_name']);

            return [
                'value' => $option['value'],
                'label' => $option['label'],
                'batch_type' => $option['batch_type'],
                'skill_id' => $skill?->id,
                'skill_name' => $option['skill_name'],
                'current_level' => $skill?->level ?? 0,
                'max_level' => $skill?->max_level ?? 0,
                'current_xp' => $skill?->xp ?? 0,
                'required_xp' => $skill?->xp_max ?? 0,
                'progress_percent' => is_null($skill) || $skill->xp_max <= 0 ? 0 : min(100, (int) floor(($skill->xp / $skill->xp_max) * 100)),
                'is_maxed' => ! is_null($skill) && $skill->level >= $skill->max_level,
                'is_available' => ! is_null($skill) && $skill->level < $skill->max_level && ! is_null($option['batch_type']),
            ];
        })->filter(fn (array $option): bool => $option['is_available'])->values();
    }

    private function relevantSkillNameMap(BatchCraftingType $type): array
    {
        $craftingSkills = [
            'weapon' => ['by' => 'name', 'name' => 'Weapon Crafting'],
            'armour' => ['by' => 'name', 'name' => 'Armour Crafting'],
            'ring' => ['by' => 'name', 'name' => 'Ring Crafting'],
            'spell' => ['by' => 'name', 'name' => 'Spell Crafting'],
        ];

        return match ($type) {
            BatchCraftingType::CRAFT => $craftingSkills,
            BatchCraftingType::CRAFT_AND_ENCHANT => array_merge($craftingSkills, [
                'enchanting' => ['by' => 'type', 'type' => SkillTypeValue::ENCHANTING->value],
            ]),
            BatchCraftingType::ENCHANT => [
                'enchanting' => ['by' => 'type', 'type' => SkillTypeValue::ENCHANTING->value],
            ],
            BatchCraftingType::ALCHEMY => [
                'alchemy' => ['by' => 'type', 'type' => SkillTypeValue::ALCHEMY->value],
            ],
            BatchCraftingType::TRINKETRY => [
                'trinketry' => ['by' => 'name', 'name' => 'Trinketry'],
            ],
            BatchCraftingType::HOLY_OILS => [],
        };
    }

    private function requestedAmount(array $progress): ?int
    {
        return $progress['craft_amount']
            ?? $progress['alchemy_amount']
            ?? $progress['holy_oil_requested_applications']
            ?? $progress['craft_set_requested']
            ?? $progress['enchant_set_total']
            ?? $progress['craft_enchant_set_total_work_units']
            ?? null;
    }

    private function completedAmount(array $progress): ?int
    {
        return $progress['craft_specific_count']
            ?? $progress['craft_enchant_specific_count']
            ?? $progress['alchemy_amount_count']
            ?? $progress['holy_oil_completed_applications']
            ?? $progress['craft_set_completed']
            ?? $progress['enchant_set_completed']
            ?? $progress['craft_enchant_set_completed_work_units']
            ?? null;
    }

    private function remainingAmount(array $progress): ?int
    {
        $requested = $this->requestedAmount($progress);
        $completed = $this->completedAmount($progress);

        if (is_null($requested) || is_null($completed)) {
            return null;
        }

        return max(0, (int) $requested - (int) $completed);
    }

    private function craftCompletionSummary(BatchCraftingType $type, array $progress): ?string
    {
        if ($type === BatchCraftingType::CRAFT_AND_ENCHANT && ($progress['craft_mode'] ?? 'experience') === 'craft_enchant_set') {
            $requested = (int) ($progress['craft_enchant_set_requested'] ?? 0);
            $completed = (int) ($progress['craft_enchant_set_completed_final_count'] ?? 0);

            if ($requested <= 0) {
                return null;
            }

            if ($completed >= $requested) {
                return 'all';
            }

            if ($completed <= 0) {
                return 'none';
            }

            return 'some';
        }

        if ($type !== BatchCraftingType::CRAFT) {
            return null;
        }

        if (! in_array($progress['craft_mode'] ?? 'experience', ['specific_item', 'craft_set'], true)) {
            return null;
        }

        $requested = $this->requestedAmount($progress);
        $completed = $this->completedAmount($progress);

        if (is_null($requested) || $requested <= 0 || is_null($completed)) {
            return null;
        }

        if ($completed >= $requested) {
            return 'all';
        }

        if ($completed <= 0) {
            return 'none';
        }

        return 'some';
    }

    private function currentItemSnapshot(BatchCrafting $batchCrafting): ?array
    {
        $progress = $batchCrafting->progress ?? [];

        if (($progress['craft_mode'] ?? null) === 'experience' && isset($progress['craft_experience_current_item_snapshot'])) {
            return $progress['craft_experience_current_item_snapshot'];
        }

        if (($progress['craft_mode'] ?? null) === 'specific_item') {
            $item = Item::find($progress['specific_item_id'] ?? null);

            if (is_null($item)) {
                return null;
            }

            return $this->itemSnapshot($item);
        }

        if (($progress['alchemy_mode'] ?? null) === 'amount') {
            $item = Item::find($progress['alchemy_item_id'] ?? null);

            if (! is_null($item)) {
                return $this->itemSnapshot($item);
            }
        }

        $lastSnapshot = collect($batchCrafting->action_log ?? [])
            ->reverse()
            ->map(fn (array $entry) => $entry['enchanted_item'] ?? $entry['crafted_item'] ?? $entry['alchemy_item'] ?? $entry['trinketry_item'] ?? $entry['oil_application']['target_item'] ?? $entry['kept_item'] ?? $entry['sold_item'] ?? $entry['destroyed_item'] ?? $entry['disenchanted_item'] ?? null)
            ->filter()
            ->first();

        return $lastSnapshot;
    }

    private function latestActionSnapshot(array $actionLog, string $key): ?array
    {
        return collect($actionLog)
            ->reverse()
            ->map(fn (array $entry) => $entry[$key] ?? null)
            ->filter()
            ->first();
    }

    private function alchemyCurrentItemSnapshot(array $progress, array $actionLog): ?array
    {
        $item = Item::find($progress['alchemy_item_id'] ?? null);

        if (! is_null($item)) {
            return $this->itemSnapshot($item);
        }

        return $this->latestActionSnapshot($actionLog, 'alchemy_item');
    }

    private function snapshotList(array $progress, string $progressKey, array $actionLog, string $actionKey): array
    {
        if (! empty($progress[$progressKey]) && is_array($progress[$progressKey])) {
            return array_values($progress[$progressKey]);
        }

        return $this->actionItemSnapshots($actionLog, $actionKey);
    }

    private function actionItemSnapshots(array $actionLog, string $actionKey): array
    {
        return collect($actionLog)
            ->map(fn (array $entry) => $entry[$actionKey] ?? null)
            ->filter()
            ->map(function (array $snapshot): array {
                return [
                    'display_name' => $snapshot['name'] ?? 'Unknown Item',
                    'quantity' => 1,
                    'snapshot' => $snapshot,
                    'slot_id' => $snapshot['slot_id_for_modal'] ?? null,
                    'crafted_at' => $snapshot['crafted_at'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function holyOilTargetSnapshots(array $progress, array $actionLog): array
    {
        if (! empty($progress['holy_oil_target_item_snapshots']) && is_array($progress['holy_oil_target_item_snapshots'])) {
            return array_values($progress['holy_oil_target_item_snapshots']);
        }

        return collect($actionLog)
            ->map(fn (array $entry) => $entry['oil_application']['target_item'] ?? null)
            ->filter()
            ->map(function (array $snapshot): array {
                return [
                    'display_name' => $snapshot['name'] ?? 'Unknown Item',
                    'quantity' => 1,
                    'snapshot' => $snapshot,
                    'slot_id' => $snapshot['slot_id_for_modal'] ?? null,
                    'crafted_at' => $snapshot['crafted_at'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function countActionKey(array $actionLog, string $actionKey): int
    {
        return collect($actionLog)->filter(fn (array $entry) => isset($entry[$actionKey]))->count();
    }

    private function goldSpent(array $actionLog, BatchCrafting $batchCrafting): int
    {
        $fromCurrency = collect($actionLog)->sum(function (array $entry): int {
            $currency = $entry['currency'] ?? null;

            if (! is_array($currency)) {
                return 0;
            }

            return max(0, (int) (($currency['before']['gold'] ?? 0) - ($currency['current']['gold'] ?? 0)));
        });

        if ($fromCurrency > 0) {
            return $fromCurrency;
        }

        $progress = $batchCrafting->progress ?? [];
        $item = Item::find($progress['specific_item_id'] ?? null);
        $completed = $progress['craft_specific_count'] ?? $progress['craft_enchant_specific_count'] ?? 0;

        return is_null($item) ? 0 : (int) $item->cost * (int) $completed;
    }

    private function humanModeLabel(BatchCrafting $batchCrafting): string
    {
        $progress = $batchCrafting->progress ?? [];

        if ($batchCrafting->batch_type === BatchCraftingType::CRAFT->value && ($progress['craft_mode'] ?? 'experience') === 'specific_item') {
            return 'Craft Amount';
        }

        if ($batchCrafting->batch_type === BatchCraftingType::CRAFT->value && ($progress['craft_mode'] ?? 'experience') === 'experience') {
            return 'Craft For Experience';
        }

        if ($batchCrafting->batch_type === BatchCraftingType::CRAFT->value && ($progress['craft_mode'] ?? 'experience') === 'craft_set') {
            return 'Craft Set';
        }

        if ($batchCrafting->batch_type === BatchCraftingType::CRAFT_AND_ENCHANT->value && ($progress['craft_mode'] ?? 'experience') === 'specific_item') {
            return 'Craft and Enchant Amount';
        }

        if ($batchCrafting->batch_type === BatchCraftingType::CRAFT_AND_ENCHANT->value && ($progress['craft_mode'] ?? 'experience') === 'experience') {
            return 'Craft and Enchant for Experience';
        }

        if ($batchCrafting->batch_type === BatchCraftingType::CRAFT_AND_ENCHANT->value && ($progress['craft_mode'] ?? 'experience') === 'craft_enchant_set') {
            return 'Craft and Enchant Set';
        }

        if ($batchCrafting->batch_type === BatchCraftingType::ENCHANT->value && ($progress['enchant_mode'] ?? 'event') === 'event') {
            return 'Enchant For Event';
        }

        return BatchCraftingType::from($batchCrafting->batch_type)->label();
    }

    private function lastAction(array $actionLog): ?string
    {
        $last = collect($actionLog)->last();

        if (is_null($last)) {
            return null;
        }

        return ucwords(str_replace('_', ' ', $last['action_type'] ?? $last['action'] ?? 'batch action'));
    }

    private function selectedSetSummary(array $progress): ?array
    {
        $setId = $progress['selected_set_id'] ?? null;

        if (is_null($setId)) {
            return null;
        }

        $set = InventorySet::find($setId);

        if (is_null($set)) {
            return null;
        }

        return [
            'id' => $set->id,
            'name' => $set->name ?? 'Set',
            'current_slots' => $set->currentSlotCount(),
            'max_slots' => $set->max_slots,
            'remaining_slots' => $set->remainingSlots(),
        ];
    }

    private function amountPreview(Character $character, BatchCraftingType $type, array $progress): ?array
    {
        if (($progress['craft_mode'] ?? null) !== 'specific_item') {
            return null;
        }

        if (! in_array($type, [BatchCraftingType::CRAFT, BatchCraftingType::CRAFT_AND_ENCHANT], true)) {
            return null;
        }

        $item = Item::find($progress['specific_item_id'] ?? null);

        if (is_null($item)) {
            return null;
        }

        $isCraftAndEnchant = $type === BatchCraftingType::CRAFT_AND_ENCHANT;
        $requested = (int) ($progress['craft_amount'] ?? 0);
        $completed = (int) ($progress['craft_specific_count'] ?? $progress['craft_enchant_specific_count'] ?? 0);
        $remainingRequested = max(0, $requested - $completed);
        $perItemCost = (int) $item->cost;

        $enchantCostPerItem = 0;
        $prefixName = null;
        $suffixName = null;

        if ($isCraftAndEnchant) {
            $affixIds = array_values(array_filter($progress['enchant_affix_ids'] ?? [], fn ($id) => ! is_null($id)));

            if (! empty($affixIds)) {
                $enchantCostPerItem = $this->enchantingService->getCostOfEnchantment($character, $affixIds, $item->id);
                $affixes = ItemAffix::whereIn('id', $affixIds)->get();
                $prefixName = $affixes->firstWhere('type', 'prefix')?->name;
                $suffixName = $affixes->firstWhere('type', 'suffix')?->name;
            }
        }

        $totalPerItemCost = $perItemCost + $enchantCostPerItem;
        $affordableByGold = $totalPerItemCost > 0 ? intdiv((int) $character->gold, $totalPerItemCost) : $remainingRequested;

        $batchCraftingSet = $this->batchCraftingSetService->getOrCreateForCharacter($character);
        $destinationRemaining = $batchCraftingSet->remainingSlots();

        $effectiveCraftableAmount = max(0, min($remainingRequested, $affordableByGold, $destinationRemaining));

        return [
            'selected_item' => $this->itemSnapshot($item),
            'requested_amount' => $requested,
            'completed_amount' => $completed,
            'remaining_requested_amount' => $remainingRequested,
            'per_item_cost' => $perItemCost,
            'enchant_cost_per_item' => $enchantCostPerItem,
            'total_per_item_cost' => $totalPerItemCost,
            'total_cost' => $totalPerItemCost * $remainingRequested,
            'available_gold' => (int) $character->gold,
            'prefix_affix_name' => $prefixName,
            'suffix_affix_name' => $suffixName,
            'enchant_can_destroy_item' => $isCraftAndEnchant,
            'destination' => 'crafted_items_set',
            'destination_current_slots' => $batchCraftingSet->currentSlotCount(),
            'destination_max_slots' => $batchCraftingSet->max_slots,
            'destination_remaining_slots' => $destinationRemaining,
            'effective_craftable_amount' => $effectiveCraftableAmount,
            'capped' => $effectiveCraftableAmount < $remainingRequested,
        ];
    }

    private function alchemyAmountPreview(Character $character, BatchCraftingType $type, array $progress): ?array
    {
        if ($type !== BatchCraftingType::ALCHEMY || ($progress['alchemy_mode'] ?? null) !== 'amount') {
            return null;
        }

        $item = Item::find($progress['alchemy_item_id'] ?? null);

        if (is_null($item)) {
            return null;
        }

        $requested = (int) ($progress['alchemy_amount'] ?? 0);
        $completed = (int) ($progress['alchemy_amount_count'] ?? 0);
        $remainingRequested = max(0, $requested - $completed);

        $goldDustCost = (int) $item->gold_dust_cost;
        $shardsCost = (int) $item->shards_cost;

        if ($character->classType()->isMerchant()) {
            $goldDustCost = (int) floor($goldDustCost - $goldDustCost * 0.10);
            $shardsCost = (int) floor($shardsCost - $shardsCost * 0.10);
        }

        if ($character->classType()->isArcaneAlchemist()) {
            $goldDustCost = (int) floor($goldDustCost - $goldDustCost * 0.15);
            $shardsCost = (int) floor($shardsCost - $shardsCost * 0.15);
        }

        $affordableByGoldDust = $goldDustCost > 0 ? intdiv((int) $character->gold_dust, $goldDustCost) : $remainingRequested;
        $affordableByShards = $shardsCost > 0 ? intdiv((int) $character->shards, $shardsCost) : $remainingRequested;
        $bagRemaining = max(0, $character->alchemy_bag_limit - $character->getAlchemyBagCount());

        $effectiveCraftableAmount = max(0, min($remainingRequested, $affordableByGoldDust, $affordableByShards, $bagRemaining));

        return [
            'selected_item' => $this->itemSnapshot($item),
            'requested_amount' => $requested,
            'completed_amount' => $completed,
            'remaining_requested_amount' => $remainingRequested,
            'gold_dust_cost_per_item' => $goldDustCost,
            'shards_cost_per_item' => $shardsCost,
            'total_gold_dust_cost' => $goldDustCost * $remainingRequested,
            'total_shards_cost' => $shardsCost * $remainingRequested,
            'available_gold_dust' => (int) $character->gold_dust,
            'available_shards' => (int) $character->shards,
            'bag_current' => $character->getAlchemyBagCount(),
            'bag_max' => $character->alchemy_bag_limit,
            'bag_remaining' => $bagRemaining,
            'effective_craftable_amount' => $effectiveCraftableAmount,
            'capped' => $effectiveCraftableAmount < $remainingRequested,
        ];
    }

    private function holyOilsSelectedPreview(Character $character, BatchCraftingType $type, array $selectedItemIds, array $selectedOilIds, array $progress): ?array
    {
        if ($type !== BatchCraftingType::HOLY_OILS || ($progress['holy_oil_mode'] ?? 'selected') === 'set') {
            return null;
        }

        if (empty($selectedItemIds) && empty($selectedOilIds)) {
            return null;
        }

        $inventory = $character->inventory;
        $oilSlots = AlchemyBagSlot::whereIn('id', $selectedOilIds)->where('character_id', $character->id)->with('item')->get();
        $availableOilUnits = (int) $oilSlots->sum('amount');
        $representativeOil = $oilSlots->first()?->item;

        $items = [];
        $totalRemainingStacks = 0;
        $totalCostIfFullyApplied = 0;

        foreach ($selectedItemIds as $itemId) {
            $slot = $inventory?->slots()->where('item_id', $itemId)->with('item')->first();

            if (is_null($slot) || is_null($slot->item)) {
                continue;
            }

            $remaining = max(0, $slot->item->holy_stacks - $slot->item->holy_stacks_applied);
            $costPerApplication = is_null($representativeOil) ? 0 : $this->holyItemService->getCost($slot->item, $representativeOil);
            $totalRemainingStacks += $remaining;
            $totalCostIfFullyApplied += $costPerApplication * $remaining;

            $items[] = [
                'item' => $this->itemSnapshot($slot->item),
                'current_stacks' => $slot->item->holy_stacks_applied,
                'max_stacks' => $slot->item->holy_stacks,
                'remaining_capacity' => $remaining,
                'gold_dust_cost_per_application' => $costPerApplication,
            ];
        }

        $maxApplicationsPossible = min($totalRemainingStacks, $availableOilUnits);

        if ($totalCostIfFullyApplied > $character->gold_dust && $totalRemainingStacks > 0) {
            $averageCost = $totalCostIfFullyApplied / $totalRemainingStacks;
            $affordable = $averageCost > 0 ? (int) floor($character->gold_dust / $averageCost) : $maxApplicationsPossible;
            $maxApplicationsPossible = min($maxApplicationsPossible, $affordable);
        }

        return [
            'items' => $items,
            'total_eligible_items' => count($items),
            'total_remaining_applications' => $totalRemainingStacks,
            'selected_oils_available' => $availableOilUnits,
            'gold_dust_available' => (int) $character->gold_dust,
            'total_cost_if_fully_applied' => $totalCostIfFullyApplied,
            'max_applications_possible' => max(0, $maxApplicationsPossible),
            'capped' => $maxApplicationsPossible < $totalRemainingStacks,
        ];
    }

    private function holyOilsSetPreview(Character $character, BatchCraftingType $type, array $selectedOilIds, array $progress): ?array
    {
        if ($type !== BatchCraftingType::HOLY_OILS || ($progress['holy_oil_mode'] ?? 'selected') !== 'set') {
            return null;
        }

        $set = InventorySet::where('id', $progress['selected_set_id'] ?? 0)->where('character_id', $character->id)->first();

        if (is_null($set)) {
            return null;
        }

        $oilSlots = AlchemyBagSlot::whereIn('id', $selectedOilIds)->where('character_id', $character->id)->with('item')->get();
        $availableOilUnits = (int) $oilSlots->sum('amount');
        $representativeOil = $oilSlots->first()?->item;

        $eligibleSlots = $set->slots()->with('item')->get()
            ->filter(fn ($slot) => ! is_null($slot->item) && ! in_array($slot->item->type, ['trinket', 'artifact'], true))
            ->filter(fn ($slot) => ($slot->item->holy_stacks - $slot->item->holy_stacks_applied) > 0);

        $items = [];
        $totalRemainingStacks = 0;
        $totalCostIfFullyApplied = 0;

        foreach ($eligibleSlots as $slot) {
            $remaining = max(0, $slot->item->holy_stacks - $slot->item->holy_stacks_applied);
            $costPerApplication = is_null($representativeOil) ? 0 : $this->holyItemService->getCost($slot->item, $representativeOil);
            $totalRemainingStacks += $remaining;
            $totalCostIfFullyApplied += $costPerApplication * $remaining;

            $items[] = [
                'item' => $this->itemSnapshot($slot->item),
                'current_stacks' => $slot->item->holy_stacks_applied,
                'max_stacks' => $slot->item->holy_stacks,
                'remaining_capacity' => $remaining,
                'gold_dust_cost_per_application' => $costPerApplication,
            ];
        }

        $maxApplicationsPossible = min($totalRemainingStacks, $availableOilUnits);

        if ($totalCostIfFullyApplied > $character->gold_dust && $totalRemainingStacks > 0) {
            $averageCost = $totalCostIfFullyApplied / $totalRemainingStacks;
            $affordable = $averageCost > 0 ? (int) floor($character->gold_dust / $averageCost) : $maxApplicationsPossible;
            $maxApplicationsPossible = min($maxApplicationsPossible, $affordable);
        }

        $maxApplicationsPossible = max(0, $maxApplicationsPossible);

        return [
            'set_name' => $set->name ?? 'Set',
            'items' => $items,
            'total_eligible_items' => count($items),
            'total_remaining_applications' => $totalRemainingStacks,
            'selected_oils_available' => $availableOilUnits,
            'gold_dust_available' => (int) $character->gold_dust,
            'total_cost_if_fully_applied' => $totalCostIfFullyApplied,
            'max_applications_possible' => $maxApplicationsPossible,
            'capped' => $maxApplicationsPossible < $totalRemainingStacks,
            'capped_message' => $maxApplicationsPossible < $totalRemainingStacks
                ? "This can apply {$maxApplicationsPossible} of {$totalRemainingStacks} remaining Holy Oil stacks with your current oils and gold dust."
                : null,
        ];
    }

    private function craftedSetPercent(?InventorySet $batchCraftingSet): int
    {
        if (is_null($batchCraftingSet) || $batchCraftingSet->max_slots <= 0) {
            return 0;
        }

        return min(100, (int) floor(($batchCraftingSet->currentSlotCount() / $batchCraftingSet->max_slots) * 100));
    }

    private function itemSnapshot(Item $item): array
    {
        return [
            'slot_id' => null,
            'item_id' => $item->id,
            'item_id_for_modal' => null,
            'slot_id_for_modal' => null,
            'name' => $item->affix_name ?? $item->name,
            'affix_name' => $item->affix_name,
            'type' => $item->type,
            'description' => $item->description,
            'crafting_type' => $item->crafting_type,
            'skill_level_required' => $item->skill_level_required,
            'base_damage' => $item->base_damage ?? 0,
            'base_ac' => $item->base_ac ?? 0,
            'base_healing' => $item->base_healing ?? 0,
            'str_modifier' => $item->str_modifier ?? 0,
            'dex_modifier' => $item->dex_modifier ?? 0,
            'agi_modifier' => $item->agi_modifier ?? 0,
            'chr_modifier' => $item->chr_modifier ?? 0,
            'dur_modifier' => $item->dur_modifier ?? 0,
            'int_modifier' => $item->int_modifier ?? 0,
            'focus_modifier' => $item->focus_modifier ?? 0,
            'skill_name' => $item->skill_name ?? null,
            'skill_bonus' => $item->skill_bonus ?? 0,
            'skill_training_bonus' => $item->skill_training_bonus ?? 0,
            'item_prefix' => $item->itemPrefix?->name,
            'item_suffix' => $item->itemSuffix?->name,
            'sockets' => [],
            'holy_stacks' => $item->holy_stacks ?? 0,
            'holy_stacks_applied' => $item->holy_stacks_applied ?? 0,
            'affix_count' => $item->affix_count ?? 0,
            'is_unique' => (bool) ($item->is_unique ?? false),
            'is_mythic' => (bool) ($item->is_mythic ?? false),
            'is_cosmic' => (bool) ($item->is_cosmic ?? false),
            'can_view' => false,
        ];
    }

    private function isInventoryFull(Character $character): bool
    {
        return $character->getInventoryCount() >= $character->inventory_max;
    }

    private function currencyAmount(Character $character, string $currency): int
    {
        return (int) ($character->{$currency} ?? 0);
    }

    private function currencyEndReason(string $currency): BatchCraftingEndReason
    {
        return match ($currency) {
            'gold' => BatchCraftingEndReason::NO_GOLD,
            'gold_dust' => BatchCraftingEndReason::NO_GOLD_DUST,
            'shards' => BatchCraftingEndReason::NO_SHARDS,
            default => BatchCraftingEndReason::NO_REQUIRED_CURRENCY,
        };
    }

    private function timerDetails(BatchCrafting $batchCrafting): array
    {
        $startedAt = $batchCrafting->started_at;
        $endsAt = $batchCrafting->ends_at;

        if (is_null($startedAt) || is_null($endsAt)) {
            return [
                'elapsed_seconds' => 0,
                'remaining_seconds' => 0,
                'elapsed_human' => $this->formatSeconds(0),
                'remaining_human' => $this->formatSeconds(0),
                'progress_percent' => 0,
            ];
        }

        $now = $batchCrafting->isRunning() ? now() : ($batchCrafting->completed_at ?? now());
        $elapsedSeconds = max(0, $startedAt->diffInSeconds($now, false));
        $remainingSeconds = $batchCrafting->isRunning() ? max(0, $now->diffInSeconds($endsAt, false)) : 0;
        $totalSeconds = max(1, $startedAt->diffInSeconds($endsAt));
        $progressPercent = min(100, max(0, (int) floor(($elapsedSeconds / $totalSeconds) * 100)));

        return [
            'elapsed_seconds' => $elapsedSeconds,
            'remaining_seconds' => $remainingSeconds,
            'elapsed_human' => $this->formatSeconds($elapsedSeconds),
            'remaining_human' => $this->formatSeconds($remainingSeconds),
            'progress_percent' => $progressPercent,
        ];
    }

    private function progressPercent(BatchCrafting $batchCrafting, int $timerProgressPercent): int
    {
        $progress = $batchCrafting->progress ?? [];
        $requested = $this->requestedAmount($progress);
        $completed = $this->completedAmount($progress);

        if (is_null($requested) || is_null($completed) || (int) $requested < 1) {
            return $timerProgressPercent;
        }

        return min(100, (int) floor(((int) $completed / (int) $requested) * 100));
    }

    private function formatSeconds(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return $hours.'h '.$minutes.'m '.$remainingSeconds.'s';
        }

        if ($minutes > 0) {
            return $minutes.'m '.$remainingSeconds.'s';
        }

        return $remainingSeconds.'s';
    }

    private function actionStatus(array $action): string
    {
        foreach (['disenchanted', 'kept', 'sold', 'destroyed', 'listed', 'applied', 'crafted', 'enchanted', 'failed', 'skipped'] as $status) {
            if (isset($action[$status.'_item']) || isset($action[$status.'_count'])) {
                return $status;
            }
        }

        if (isset($action['enchanted'])) {
            return 'enchanted';
        }

        if (isset($action['disenchanted_item'])) {
            return 'disenchanted';
        }

        if (isset($action['oil_application'])) {
            return 'applied';
        }

        if (isset($action['failure'])) {
            return 'failed';
        }

        if (isset($action['enchanted_item'])) {
            return 'enchanted';
        }

        if (isset($action['crafted_item']) || isset($action['trinketry_item']) || isset($action['alchemy_item'])) {
            return 'crafted';
        }

        return 'skipped';
    }

    private function countActionStatus(array $actionLog, string $status): int
    {
        return collect($actionLog)->filter(fn (array $entry) => ($entry['status'] ?? null) === $status)->count();
    }

    private function keptSetSummary(BatchCrafting $batchCrafting): ?array
    {
        if ($batchCrafting->ended_reason !== BatchCraftingEndReason::NO_GOLD->value) {
            return null;
        }

        $items = collect($batchCrafting->action_log ?? [])
            ->pluck('kept_item')
            ->filter()
            ->values()
            ->all();

        if (empty($items)) {
            return null;
        }

        return [
            'message' => 'Oops, you ran out of gold, but we kept the best set we could build for you. Any completed pieces are in your inventory.',
            'items' => $items,
        ];
    }

    private function nextAction(BatchCrafting $batchCrafting): ?string
    {
        $progress = $batchCrafting->progress ?? [];

        if (($progress['event_mode'] ?? false) === true && ($progress['event_action'] ?? null) === 'enchant') {
            return match ($progress['event_enchant_phase'] ?? 'enchant_event_inventory') {
                'enchant_event_inventory' => 'double-enchant event-crafted items',
                'craft_fallback_set' => 'craft fallback items and double-enchant them',
                'enchant_fallback_set' => 'double-enchant fallback items',
                default => 'wait for next tick',
            };
        }

        if (($progress['craft_enchant_phase'] ?? null) === 'enchant') {
            return 'enchant';
        }

        return match ($batchCrafting->batch_type) {
            BatchCraftingType::CRAFT->value => 'craft',
            BatchCraftingType::CRAFT_AND_ENCHANT->value => 'craft',
            BatchCraftingType::ENCHANT->value => 'enchant',
            BatchCraftingType::ALCHEMY->value => 'alchemy',
            BatchCraftingType::HOLY_OILS->value => 'holy_oil',
            BatchCraftingType::TRINKETRY->value => 'trinketry',
            default => null,
        };
    }

    private function tickDelaySeconds(BatchCraftingType $type, array $progress): int
    {
        return self::RECURRING_DELAY_SECONDS;
    }

    private function isSetMode(BatchCraftingType $type, array $progress): bool
    {
        if ($type === BatchCraftingType::CRAFT && ($progress['craft_mode'] ?? 'experience') === 'craft_set') {
            return true;
        }

        if ($type === BatchCraftingType::CRAFT_AND_ENCHANT && ($progress['craft_mode'] ?? 'experience') === 'craft_enchant_set') {
            return true;
        }

        if ($type === BatchCraftingType::ENCHANT && ($progress['enchant_mode'] ?? 'event') === 'set') {
            return true;
        }

        return false;
    }

    private function eventCurrentPhaseLabel(BatchCrafting $batchCrafting): ?string
    {
        $progress = $batchCrafting->progress ?? [];

        if (($progress['event_mode'] ?? false) !== true) {
            return null;
        }

        if (! $batchCrafting->isRunning()) {
            return $this->eventStopReason($batchCrafting);
        }

        if (($progress['event_action'] ?? null) === 'craft') {
            return 'Crafting event items';
        }

        return match ($progress['event_enchant_phase'] ?? 'enchant_event_inventory') {
            'enchant_event_inventory' => 'Enchanting event-crafted items',
            'craft_fallback_set' => 'Crafting fallback items',
            'enchant_fallback_set' => 'Double-enchanting fallback items',
            default => 'Waiting for next tick',
        };
    }

    private function eventStopReason(BatchCrafting $batchCrafting): ?string
    {
        return match ($batchCrafting->ended_reason) {
            BatchCraftingEndReason::EVENT_GOAL_COMPLETE->value => 'Event goal complete. Batch stopped.',
            BatchCraftingEndReason::EVENT_NOT_RUNNING->value => 'Event is no longer running. Batch stopped.',
            BatchCraftingEndReason::EVENT_WRONG_MAP->value => 'You are no longer on the event map. Batch stopped.',
            BatchCraftingEndReason::EVENT_STEP_CHANGED->value => 'Event phase changed. Batch stopped.',
            BatchCraftingEndReason::EVENT_NO_EVENT_ITEMS_TO_ENCHANT->value => 'Stopped because no event items and fallback cannot continue.',
            BatchCraftingEndReason::EVENT_NO_AFFIXES->value => 'Stopped because no valid enchantments are available.',
            default => null,
        };
    }

    private function eventBatchData(Character $character, ?BatchCrafting $batchCrafting = null): array
    {
        $event = Event::whereNotNull('current_event_goal_step')->first();
        $craftGoal = $this->globalEventGoalEligibilityService->currentCraftingGoalFor($character);
        $enchantGoal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);
        $goal = $craftGoal ?? $enchantGoal;

        return [
            'can_craft_for_event' => ! is_null($craftGoal),
            'can_enchant_for_event' => ! is_null($enchantGoal),
            'event_type' => $event?->type,
            'event_name' => is_null($event) ? null : (new EventType($event->type))->getNameForEvent(),
            'current_step' => $event?->current_event_goal_step,
            'goal_remaining' => is_null($goal) ? null : $this->goalRemaining($goal),
            'actions_per_tick' => self::ITEMS_PER_RECURRING_TICK,
            'tick_rate_seconds' => self::RECURRING_DELAY_SECONDS,
            'max_runtime_hours' => self::DURATION_HOURS,
            'active_event_mode' => (bool) (($batchCrafting?->progress ?? [])['event_mode'] ?? false),
            'crafting_skills_maxed' => $this->areAllCraftingSkillsMaxed($character),
            'alchemy_maxed' => $this->isSkillMaxedByName($character, 'Alchemy'),
            'trinketry_maxed' => $this->isSkillMaxedByName($character, 'Trinketry'),
            'enchanting_maxed' => $this->isEnchantingMaxed($character),
        ];
    }

    private function goalRemaining(GlobalEventGoal $goal): int
    {
        if (! is_null($goal->max_crafts)) {
            return max(0, $goal->max_crafts - $goal->total_crafts);
        }

        if (! is_null($goal->max_enchants)) {
            return max(0, $goal->max_enchants - $goal->total_enchants);
        }

        if (! is_null($goal->max_kills)) {
            return max(0, $goal->max_kills - $goal->total_kills);
        }

        return 0;
    }

    private function eventGoalProgress(array $progress): ?array
    {
        $goalId = $progress['event_goal_id'] ?? null;

        if (is_null($goalId)) {
            return null;
        }

        $goal = GlobalEventGoal::find($goalId);

        if (is_null($goal)) {
            return null;
        }

        if (($progress['event_action'] ?? null) === 'craft') {
            return [
                'current' => $goal->total_crafts,
                'max' => $goal->max_crafts,
            ];
        }

        if (($progress['event_action'] ?? null) === 'enchant') {
            return [
                'current' => $goal->total_enchants,
                'max' => $goal->max_enchants,
            ];
        }

        return null;
    }

    private function eventCharacterContribution(Character $character, array $progress): ?array
    {
        $goalId = $progress['event_goal_id'] ?? null;

        if (is_null($goalId)) {
            return null;
        }

        $goal = GlobalEventGoal::find($goalId);

        if (is_null($goal)) {
            return null;
        }

        if (($progress['event_action'] ?? null) === 'craft') {
            $current = $character->globalEventCrafts()->where('global_event_goal_id', $goal->id)->first()?->crafts ?? 0;
        } elseif (($progress['event_action'] ?? null) === 'enchant') {
            $current = $character->globalEventEnchants()->where('global_event_goal_id', $goal->id)->first()?->enchants ?? 0;
        } else {
            $current = 0;
        }

        return [
            'current' => $current,
            'reward_threshold' => $this->eventGoalsServiceAmountNeeded($goal),
        ];
    }

    private function eventGoalsServiceAmountNeeded(GlobalEventGoal $goal): int
    {
        $participants = $goal->globalEventParticipation()->count();

        if ($participants > 0) {
            return (int) round($goal->reward_every / $participants);
        }

        return (int) $goal->reward_every;
    }

    private function eventGoalCompletedAfterTick(BatchCrafting $batchCrafting): bool
    {
        $progress = $batchCrafting->progress ?? [];

        if (($progress['event_mode'] ?? false) !== true) {
            return false;
        }

        $goal = GlobalEventGoal::find($progress['event_goal_id'] ?? null);

        if (is_null($goal)) {
            return false;
        }

        if (($progress['event_action'] ?? null) === 'craft') {
            return ! is_null($goal->max_crafts) && $goal->total_crafts >= $goal->max_crafts;
        }

        if (($progress['event_action'] ?? null) === 'enchant') {
            return ! is_null($goal->max_enchants) && $goal->total_enchants >= $goal->max_enchants;
        }

        return false;
    }

    private function validatedProgress(Character $character, BatchCraftingType $type, array $data): array
    {
        $progress = $data['progress'] ?? [];

        if (in_array($type, [BatchCraftingType::CRAFT, BatchCraftingType::CRAFT_AND_ENCHANT], true)) {
            $mode = $progress['craft_mode'] ?? 'experience';

            if ($mode === 'event') {
                if ($type !== BatchCraftingType::CRAFT) {
                    throw ValidationException::withMessages([
                        'progress.craft_mode' => 'Event crafting is only available for craft batches.',
                    ]);
                }

                return $this->eventCraftProgress($character);
            }

            if ($mode === 'experience') {
                if ($type === BatchCraftingType::CRAFT) {
                    if ($this->areAllCraftingSkillsMaxed($character)) {
                        throw ValidationException::withMessages([
                            'progress.craft_mode' => 'Weapon Crafting, Armour Crafting, Ring Crafting, and Spell Crafting are all maxed, so this character cannot batch craft for experience.',
                        ]);
                    }

                    unset($progress['craft_experience_skill']);
                }

                if ($type === BatchCraftingType::CRAFT_AND_ENCHANT) {
                    if ($this->areAllCraftingSkillsMaxed($character) && $this->isEnchantingMaxed($character)) {
                        throw ValidationException::withMessages([
                            'progress.craft_mode' => 'Weapon Crafting, Armour Crafting, Ring Crafting, Spell Crafting, and Enchanting are all maxed, so this character cannot batch craft and enchant for experience.',
                        ]);
                    }

                    unset($progress['craft_experience_skill']);
                }
            }

            if ($mode === 'specific_item') {
                $this->validateSpecificCraftItem($character, $progress);
            }

            if ($type === BatchCraftingType::CRAFT_AND_ENCHANT && $mode === 'specific_item') {
                $this->validateEnchantAffixes($character, $progress['enchant_affix_ids'] ?? []);
            }

            if ($type === BatchCraftingType::CRAFT && $mode === 'craft_set') {
                return $this->craftSetProgress($character, $progress);
            }

            if ($type === BatchCraftingType::CRAFT_AND_ENCHANT && $mode === 'craft_enchant_set') {
                return $this->craftEnchantSetProgress($character, $progress);
            }
        }

        if ($type === BatchCraftingType::ENCHANT) {
            $mode = $progress['enchant_mode'] ?? 'event';

            if ($mode === 'set') {
                throw ValidationException::withMessages([
                    'progress.enchant_mode' => 'Standalone Enchant Set is no longer available. Use Enchant For Event.',
                ]);
            }

            return $this->eventEnchantProgress($character);
        }

        if ($type === BatchCraftingType::ALCHEMY) {
            $mode = $progress['alchemy_mode'] ?? 'experience';
            $progress['alchemy_mode'] = $mode;

            if ($mode === 'experience') {
                $this->rejectMaxedSkill($character, 'Alchemy', 'progress.alchemy_mode');
            }

            if ($mode === 'amount') {
                $progress['alchemy_amount'] = (int) ($progress['alchemy_amount'] ?? $progress['craft_amount'] ?? 0);
                $progress['alchemy_item_id'] = (int) ($progress['alchemy_item_id'] ?? 0);
            }
        }

        if ($type === BatchCraftingType::TRINKETRY) {
            $progress['trinketry_mode'] = 'experience';
            $this->rejectMaxedSkill($character, 'Trinketry', 'progress.trinketry_mode');
        }

        if ($type === BatchCraftingType::HOLY_OILS) {
            $mode = $progress['holy_oil_mode'] ?? 'selected';
            $progress['holy_oil_mode'] = $mode;

            if ($mode === 'set') {
                return array_merge($progress, $this->holyOilSetProgress($character, $progress));
            }
        }

        return $progress;
    }

    private function craftSetProgress(Character $character, array $progress): array
    {
        $set = $this->validateOwnedInventorySet($character, (int) ($progress['selected_set_id'] ?? 0), 'progress.selected_set_id');

        if ($set->slots()->count() > 0) {
            throw ValidationException::withMessages([
                'progress.selected_set_id' => 'The selected set must be empty before starting this batch.',
            ]);
        }

        $queue = $this->processor->craftSetQueue();

        return [
            'craft_mode' => 'craft_set',
            'selected_set_id' => $set->id,
            'craft_set_queue' => $queue,
            'craft_set_index' => 0,
            'craft_set_requested' => count($queue),
            'craft_set_completed' => 0,
        ];
    }

    private function craftEnchantSetProgress(Character $character, array $progress): array
    {
        $set = $this->validateOwnedInventorySet($character, (int) ($progress['selected_set_id'] ?? 0), 'progress.selected_set_id');

        if ($set->slots()->count() > 0) {
            throw ValidationException::withMessages([
                'progress.selected_set_id' => 'The selected set must be empty before starting this batch.',
            ]);
        }

        $queue = $this->processor->craftSetQueue();
        $keys = $this->processor->craftEnchantSetPlanKeys($queue);
        $plan = is_array($progress['enchant_plan'] ?? null) ? $progress['enchant_plan'] : [];

        $this->validateCraftEnchantSetPlan($character, $keys, $plan);

        return [
            'craft_mode' => 'craft_enchant_set',
            'selected_set_id' => $set->id,
            'craft_enchant_set_queue' => $queue,
            'craft_enchant_set_keys' => $keys,
            'enchant_plan' => $plan,
            'craft_enchant_set_requested' => count($queue),
            'craft_enchant_set_phase' => 'crafting',
            'craft_enchant_set_craft_index' => 0,
            'craft_enchant_set_enchant_index' => 0,
            'craft_enchant_set_finalize_index' => 0,
            'craft_enchant_set_crafted_slots' => [],
            'craft_enchant_set_prefix_applied_count' => 0,
            'craft_enchant_set_suffix_applied_count' => 0,
            'craft_enchant_set_total_work_units' => count($queue) * 3,
            'craft_enchant_set_completed_work_units' => 0,
        ];
    }

    private function validateCraftEnchantSetPlan(Character $character, array $keys, array $plan): void
    {
        $missingKeys = array_values(array_diff($keys, array_keys($plan)));

        if (! empty($missingKeys)) {
            throw ValidationException::withMessages([
                'progress.enchant_plan' => 'Every item in the full set must have a prefix and suffix selected.',
            ]);
        }

        $prefixIds = [];
        $suffixIds = [];

        foreach ($keys as $key) {
            $entry = is_array($plan[$key] ?? null) ? $plan[$key] : [];
            $prefixId = isset($entry['prefix_affix_id']) ? (int) $entry['prefix_affix_id'] : null;
            $suffixId = isset($entry['suffix_affix_id']) ? (int) $entry['suffix_affix_id'] : null;

            if (is_null($prefixId)) {
                throw ValidationException::withMessages([
                    'progress.enchant_plan' => 'Every item in the full set must have a prefix selected.',
                ]);
            }

            if (is_null($suffixId)) {
                throw ValidationException::withMessages([
                    'progress.enchant_plan' => 'Every item in the full set must have a suffix selected.',
                ]);
            }

            $prefixIds[] = $prefixId;
            $suffixIds[] = $suffixId;
        }

        $prefixAffixes = ItemAffix::whereIn('id', $prefixIds)->get();
        $suffixAffixes = ItemAffix::whereIn('id', $suffixIds)->get();

        if ($prefixAffixes->count() !== count(array_unique($prefixIds)) || $prefixAffixes->contains(fn (ItemAffix $affix) => $affix->type !== 'prefix')) {
            throw ValidationException::withMessages([
                'progress.enchant_plan' => 'One or more selected prefix enchantments are invalid.',
            ]);
        }

        if ($suffixAffixes->count() !== count(array_unique($suffixIds)) || $suffixAffixes->contains(fn (ItemAffix $affix) => $affix->type !== 'suffix')) {
            throw ValidationException::withMessages([
                'progress.enchant_plan' => 'One or more selected suffix enchantments are invalid.',
            ]);
        }

        $this->validateAffixesAvailableByEnchantingLevel($character, $prefixAffixes->merge($suffixAffixes));
    }

    private function enchantSetProgress(Character $character, array $progress): array
    {
        $set = $this->validateOwnedInventorySet($character, (int) ($progress['selected_set_id'] ?? 0), 'progress.selected_set_id');
        $this->validateEnchantAffixes($character, $progress['enchant_affix_ids'] ?? []);
        $eligibleTotal = $set->slots()->with('item')->get()
            ->filter(fn ($slot) => $this->processor->isEnchantableSetItem($slot->item))
            ->count();

        if ($eligibleTotal < 1) {
            throw ValidationException::withMessages([
                'progress.selected_set_id' => 'The selected set has no items eligible for enchanting.',
            ]);
        }

        return [
            'enchant_mode' => 'set',
            'selected_set_id' => $set->id,
            'enchant_affix_ids' => array_values($progress['enchant_affix_ids']),
            'enchant_set_total' => $eligibleTotal,
            'enchant_set_completed' => 0,
            'enchant_set_skipped' => 0,
        ];
    }

    private function holyOilSetProgress(Character $character, array $progress): array
    {
        $set = $this->validateOwnedInventorySet($character, (int) ($progress['selected_set_id'] ?? 0), 'progress.selected_set_id');

        $eligibleSlots = $set->slots()->with('item')->get()
            ->filter(fn ($slot) => ! is_null($slot->item) && ! in_array($slot->item->type, ['trinket', 'artifact'], true))
            ->filter(fn ($slot) => ($slot->item->holy_stacks - $slot->item->holy_stacks_applied) > 0);
        $totalRemainingStacks = $eligibleSlots->sum(fn ($slot) => max(0, $slot->item->holy_stacks - $slot->item->holy_stacks_applied));

        return [
            'holy_oil_mode' => 'set',
            'selected_set_id' => $set->id,
            'holy_oil_eligible_items' => $eligibleSlots->count(),
            'holy_oil_total_stacks' => $totalRemainingStacks,
            'holy_oil_requested_applications' => $totalRemainingStacks,
            'holy_oil_completed_applications' => 0,
        ];
    }

    private function validateOwnedInventorySet(Character $character, int $setId, string $field): InventorySet
    {
        $set = InventorySet::where('id', $setId)->where('character_id', $character->id)->first();

        if (is_null($set) || $set->isBatchCraftingSet()) {
            throw ValidationException::withMessages([
                $field => 'The selected set does not belong to this character.',
            ]);
        }

        return $set;
    }

    private function eventCraftProgress(Character $character): array
    {
        $goal = $this->globalEventGoalEligibilityService->currentCraftingGoalFor($character);

        if (is_null($goal)) {
            throw ValidationException::withMessages([
                'progress.craft_mode' => 'Event crafting is not available for this character.',
            ]);
        }

        return [
            'event_mode' => true,
            'event_action' => 'craft',
            'event_type' => $goal->event_type,
            'event_goal_id' => $goal->id,
            'event_step' => GlobalEventSteps::CRAFT,
            'craft_mode' => 'event',
            'tick_delay_seconds' => self::RECURRING_DELAY_SECONDS,
            'event_actions_per_tick' => self::ITEMS_PER_RECURRING_TICK,
            'event_craft_queue' => [
                ['type' => 'weapon', 'crafting_type' => 'weapon'],
                ['type' => 'armour', 'crafting_type' => 'armour'],
                ['type' => 'ring', 'crafting_type' => 'ring'],
                ['type' => 'spell_damage', 'crafting_type' => 'spell'],
                ['type' => 'spell_healing', 'crafting_type' => 'spell'],
            ],
            'event_craft_index' => 0,
        ];
    }

    private function eventEnchantProgress(Character $character): array
    {
        $goal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);

        if (is_null($goal)) {
            throw ValidationException::withMessages([
                'batch_type' => 'Event enchanting is not available for this character.',
            ]);
        }

        return [
            'event_mode' => true,
            'event_action' => 'enchant',
            'event_type' => $goal->event_type,
            'event_goal_id' => $goal->id,
            'event_step' => GlobalEventSteps::ENCHANT,
            'enchant_mode' => 'event',
            'event_enchant_phase' => 'enchant_event_inventory',
            'event_fallback_phase' => null,
            'event_fallback_slot_ids' => [],
            'tick_delay_seconds' => self::RECURRING_DELAY_SECONDS,
            'event_actions_per_tick' => self::ITEMS_PER_RECURRING_TICK,
        ];
    }

    private function validateSpecificCraftItem(Character $character, array $progress): void
    {
        if (is_null($this->craftingService)) {
            return;
        }

        $craftingType = $progress['specific_crafting_type'] ?? null;
        $itemId = (int) ($progress['specific_item_id'] ?? 0);

        if (is_null($craftingType) || $itemId <= 0) {
            throw ValidationException::withMessages([
                'progress.specific_item_id' => 'Select a valid item to craft.',
            ]);
        }

        $craftableItems = $this->specificCraftableItems($character, $craftingType);

        if ($craftableItems->first(fn ($item) => (int) $item->id === $itemId) === null) {
            throw ValidationException::withMessages([
                'progress.specific_item_id' => 'The selected item is not craftable by this character.',
            ]);
        }
    }

    private function specificCraftableItems(Character $character, string $craftingType)
    {
        return $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => $craftingType,
        ], false);
    }

    private function validateEnchantAffixes(Character $character, array $affixIds): void
    {
        if (empty($affixIds)) {
            throw ValidationException::withMessages([
                'progress.enchant_affix_ids' => 'Select at least one enchantment.',
            ]);
        }

        $affixes = ItemAffix::whereIn('id', $affixIds)->get();

        if ($affixes->count() !== count($affixIds)) {
            throw ValidationException::withMessages([
                'progress.enchant_affix_ids' => 'One or more selected enchantments are invalid.',
            ]);
        }

        $this->validateAffixesAvailableByEnchantingLevel($character, $affixes);
    }

    private function validateAffixesAvailableByEnchantingLevel(Character $character, Collection $affixes): void
    {
        $enchantingLevel = $this->enchantingSkillLevel($character);

        if ($affixes->contains(fn (ItemAffix $affix) => $affix->skill_level_required > $enchantingLevel)) {
            throw ValidationException::withMessages([
                'progress.enchant_affix_ids' => 'One or more selected enchantments are above the current Enchanting level.',
            ]);
        }
    }

    private function enchantingSkillLevel(Character $character): int
    {
        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->first();

        return $skill?->level ?? 0;
    }

    private function rejectMaxedSkill(Character $character, string $skillName, string $field): void
    {
        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('name', $skillName))
            ->with('baseSkill')
            ->first();

        if (! is_null($skill) && $skill->level >= $skill->max_level) {
            throw ValidationException::withMessages([
                $field => $skillName . ' is already maxed.',
            ]);
        }
    }

    private function areAllCraftingSkillsMaxed(Character $character): bool
    {
        $craftingSkills = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->whereIn('name', [
                'Weapon Crafting',
                'Armour Crafting',
                'Ring Crafting',
                'Spell Crafting',
            ]))
            ->with('baseSkill')
            ->get();

        return $craftingSkills->isNotEmpty() && $craftingSkills->every(fn ($skill) => $skill->level >= $skill->max_level);
    }

    private function isSkillMaxedByName(Character $character, string $skillName): bool
    {
        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('name', $skillName))
            ->with('baseSkill')
            ->first();

        return ! is_null($skill) && $skill->level >= $skill->max_level;
    }

    private function isEnchantingMaxed(Character $character): bool
    {
        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->with('baseSkill')
            ->first();

        return ! is_null($skill) && $skill->level >= $skill->max_level;
    }

    private function craftingSkillName(string $craftingType): string
    {
        if (in_array($craftingType, ['dagger', 'sword', 'claw', 'wand', 'censer', 'stave', 'hammer', 'bow', 'gun', 'fan', 'mace', 'scratch-awl', 'weapon'], true)) {
            return 'Weapon Crafting';
        }

        return match ($craftingType) {
            'ring' => 'Ring Crafting',
            'spell', 'spell_damage', 'spell_healing' => 'Spell Crafting',
            default => 'Armour Crafting',
        };
    }

    private function validateHolyOilSelections(Character $character, array $data): void
    {
        $mode = $data['progress']['holy_oil_mode'] ?? 'selected';
        $selectedOils = $data['selected_oils'] ?? [];

        if (empty($selectedOils)) {
            throw ValidationException::withMessages([
                'selected_oils' => 'Select at least one usable Holy Oil.',
            ]);
        }

        $this->validateHolyOilOilSelections($character, $selectedOils);

        if ($mode === 'set') {
            return;
        }

        $selectedItems = $data['selected_items'] ?? [];

        if (empty($selectedItems)) {
            throw ValidationException::withMessages([
                'selected_items' => 'Select at least one eligible item for Holy Oils.',
            ]);
        }

        foreach ($selectedItems as $itemId) {
            $slot = $character->inventory?->slots()
                ->where('item_id', $itemId)
                ->with('item.appliedHolyStacks')
                ->first();

            if (is_null($slot) || is_null($slot->item)) {
                throw ValidationException::withMessages([
                    'selected_items' => 'One or more selected items do not belong to this character.',
                ]);
            }

            if (in_array($slot->item->type, ['trinket', 'artifact']) || ($slot->item->holy_stacks - $slot->item->holy_stacks_applied) <= 0) {
                throw ValidationException::withMessages([
                    'selected_items' => 'One or more selected items cannot receive Holy Oils.',
                ]);
            }
        }
    }

    private function validateHolyOilOilSelections(Character $character, array $selectedOils): void
    {
        foreach ($selectedOils as $oilSlotId) {
            $oilSlot = AlchemyBagSlot::where('id', $oilSlotId)
                ->where('character_id', $character->id)
                ->with('item')
                ->first();

            if (is_null($oilSlot) || is_null($oilSlot->item)) {
                throw ValidationException::withMessages([
                    'selected_oils' => 'One or more selected oils do not belong to this character.',
                ]);
            }

            if ($oilSlot->amount <= 0 || ! $oilSlot->item->can_use_on_other_items || is_null($oilSlot->item->holy_level)) {
                throw ValidationException::withMessages([
                    'selected_oils' => 'One or more selected oils cannot be used as Holy Oils.',
                ]);
            }
        }
    }
}
