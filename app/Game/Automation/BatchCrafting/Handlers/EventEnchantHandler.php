<?php

namespace App\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Automation\BatchCrafting\Attributes\HandlesBatchCraftingMode;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingHandler;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftEventTargetType;
use App\Game\Automation\BatchCrafting\Enums\EnchantEventPhase;
use App\Game\Automation\BatchCrafting\Enums\EnchantingBatchMode;
use App\Game\Automation\BatchCrafting\Services\CraftingBatchAttemptService;
use App\Game\Automation\BatchCrafting\Services\EventEnchantTargetService;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Automation\BatchCrafting\Values\ResolvedEventEnchantTarget;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Values\CraftingMessageMode;

#[HandlesBatchCraftingMode(BatchCraftingType::ENCHANT, EnchantingBatchMode::EVENT)]
class EventEnchantHandler implements BatchCraftingHandler
{
    /**
     * @param  EventEnchantTargetService  $eventEnchantTargetService
     * @param  EnchantingService  $enchantingService
     * @param  CraftingService  $craftingService
     * @param  CraftingBatchAttemptService  $craftingBatchAttemptService
     */
    public function __construct(
        private readonly EventEnchantTargetService $eventEnchantTargetService,
        private readonly EnchantingService $enchantingService,
        private readonly CraftingService $craftingService,
        private readonly CraftingBatchAttemptService $craftingBatchAttemptService,
    ) {}

    /**
     * Execute one Enchant For Event Batch Crafting action slot for the running batch.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  Character  $character  The character running the batch.
     * @return BatchCraftingOperationResult The outcome of the action slot.
     */
    public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
    {
        $goal = $this->eventEnchantTargetService->currentGoal($character);

        if (is_null($goal)) {
            return BatchCraftingOperationResult::ended($this->eventEnchantTargetService->resolveUnavailableReason($character));
        }

        $phase = EnchantEventPhase::from($batchCrafting->progress['event_enchant_phase']);

        return match ($phase) {
            EnchantEventPhase::ENCHANT_EVENT_INVENTORY, EnchantEventPhase::ENCHANT_FALLBACK_SET => $this->handleEnchantPhase($batchCrafting, $character, $goal),
            EnchantEventPhase::CRAFT_FALLBACK_SET => $this->handleCraftFallbackPhase($batchCrafting, $character, $goal),
        };
    }

    /**
     * Enchant the next available Event Crafting Inventory item, real or fallback-crafted.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  Character  $character  The character running the batch.
     * @param  GlobalEventGoal  $goal  The current Enchant Event goal.
     * @return BatchCraftingOperationResult The outcome of this action slot.
     */
    private function handleEnchantPhase(BatchCrafting $batchCrafting, Character $character, GlobalEventGoal $goal): BatchCraftingOperationResult
    {
        $target = $this->eventEnchantTargetService->resolveNextInventoryTarget($character, $goal);

        if (is_null($target)) {
            return $this->handleEmptyInventory($batchCrafting, $goal);
        }

        return $this->attemptEnchant($batchCrafting, $character, $target);
    }

    /**
     * Handle an empty Event Crafting Inventory by ending or moving into the fallback craft phase.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  GlobalEventGoal  $goal  The current Enchant Event goal.
     * @return BatchCraftingOperationResult The outcome of this action slot.
     */
    private function handleEmptyInventory(BatchCrafting $batchCrafting, GlobalEventGoal $goal): BatchCraftingOperationResult
    {
        $goal = $goal->fresh();

        if (! is_null($goal) && $goal->total_enchants >= $goal->max_enchants) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::EVENT_GOAL_COMPLETE);
        }

        $this->setPhase($batchCrafting, EnchantEventPhase::CRAFT_FALLBACK_SET);

        return BatchCraftingOperationResult::inProgress();
    }

    /**
     * Resolve the cheapest eligible Event affixes and attempt to enchant the resolved target.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  Character  $character  The character running the batch.
     * @param  ResolvedEventEnchantTarget  $target  The resolved inventory slot and item.
     * @return BatchCraftingOperationResult The outcome of this action slot.
     */
    private function attemptEnchant(BatchCrafting $batchCrafting, Character $character, ResolvedEventEnchantTarget $target): BatchCraftingOperationResult
    {
        $affixes = $this->enchantingService->findEventBatchAffixes($character);

        if (is_null($affixes['prefix']) && is_null($affixes['suffix'])) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_ENCHANTING_AFFIX);
        }

        if ($affixes['intelligence_blocked']) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::INT_TOO_LOW);
        }

        $item = $target->item;
        $progress = $batchCrafting->fresh()->progress;
        $progress['current_item_id'] = $item->id;
        $progress['current_item_name'] = $item->affix_name ?? $item->name;
        $progress['current_prefix_name'] = $affixes['prefix']?->name;
        $progress['current_suffix_name'] = $affixes['suffix']?->name;
        $batchCrafting->update(['progress' => $progress]);

        $affixIds = array_values(array_filter([$affixes['prefix']?->id, $affixes['suffix']?->id]));
        $cost = $this->enchantingService->getCostOfEnchantment($character, $affixIds, $item->id);
        $enchantingXpBefore = $this->enchantingService->findEnchantingSkill($character)?->xp ?? 0;

        $succeeded = $this->enchantingService->enchant($character, ['affix_ids' => $affixIds, 'enchant_for_event' => true], $target->slot, $cost);

        $enchantingXpGained = max(0, ($this->enchantingService->findEnchantingSkill($character)?->xp ?? 0) - $enchantingXpBefore);
        $this->persistXpGained($batchCrafting, 0, $enchantingXpGained);

        if (! $succeeded) {
            return BatchCraftingOperationResult::failed($cost);
        }

        return BatchCraftingOperationResult::applied($cost);
    }

    /**
     * Craft one fallback item and add it to the character's Event Crafting Inventory.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  Character  $character  The character running the batch.
     * @param  GlobalEventGoal  $goal  The current Enchant Event goal.
     * @return BatchCraftingOperationResult The outcome of this action slot.
     */
    private function handleCraftFallbackPhase(BatchCrafting $batchCrafting, Character $character, GlobalEventGoal $goal): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->fresh()->progress;
        $cyclePosition = $progress['fallback_cycle_position'];
        $progress['fallback_cycle_position'] = ($cyclePosition + 1) % $this->eventEnchantTargetService->fallbackCycleSize();
        $batchCrafting->update(['progress' => $progress]);

        $resolved = $this->eventEnchantTargetService->resolveFallbackCraftTarget($character, $cyclePosition);

        if (is_null($resolved)) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::EVENT_NO_CRAFTABLE_ITEMS);
        }

        $target = $resolved['target'];
        $item = $resolved['item'];
        $craftingType = $target === CraftEventTargetType::WEAPON ? $item->type : $target->craftingType();
        $goldCost = $this->craftingService->getItemCostForAutomation($character, $item);

        $craftResult = $this->craftingService->craftForBatch($character, $item, $craftingType, CraftingMessageMode::BATCH_CRAFTING);

        if (! $craftResult['success']) {
            return $this->craftingBatchAttemptService->translateFailure($craftResult['reason'], $goldCost);
        }

        $this->eventEnchantTargetService->addFallbackItemToInventory($character, $goal, $craftResult['item']);
        $this->persistXpGained($batchCrafting, $craftResult['xp_gained'], 0);
        $this->setPhase($batchCrafting, EnchantEventPhase::ENCHANT_FALLBACK_SET);

        return BatchCraftingOperationResult::inProgress();
    }

    /**
     * Persist the batch's cumulative Crafting and Enchanting XP using this action's factual XP gained.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  int  $craftingXpGained  The factual Crafting XP gained by this action.
     * @param  int  $enchantingXpGained  The factual Enchanting XP gained by this action.
     * @return void This method does not return a value.
     */
    private function persistXpGained(BatchCrafting $batchCrafting, int $craftingXpGained, int $enchantingXpGained): void
    {
        $progress = $batchCrafting->fresh()->progress;
        $progress['crafting_xp_gained'] += $craftingXpGained;
        $progress['enchanting_xp_gained'] += $enchantingXpGained;
        $batchCrafting->update(['progress' => $progress]);
    }

    /**
     * Persist the run's current Enchant For Event phase.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  EnchantEventPhase  $phase  The phase to persist.
     * @return void This method does not return a value.
     */
    private function setPhase(BatchCrafting $batchCrafting, EnchantEventPhase $phase): void
    {
        $progress = $batchCrafting->fresh()->progress;
        $progress['event_enchant_phase'] = $phase->value;
        $batchCrafting->update(['progress' => $progress]);
    }
}
