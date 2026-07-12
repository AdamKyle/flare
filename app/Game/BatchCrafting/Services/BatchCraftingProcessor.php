<?php

namespace App\Game\BatchCrafting\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\SetSlot;
use App\Flare\Models\Skill;
use App\Flare\Transformers\ItemTransformer;
use App\Flare\Values\ItemHolyValue;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use App\Game\Character\CharacterInventory\Services\UseItemService;
use App\Game\Character\CharacterInventory\Values\ArmourType;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Messages\Handlers\ServerMessageHandler;
use App\Game\NpcActions\WorkBench\Services\HolyItemService;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Game\Skills\Handlers\HandleUpdatingCraftingGlobalEventGoal;
use App\Game\Skills\Values\SkillTypeValue;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Facades\App\Flare\Calculators\SkillXPCalculator;
use Illuminate\Support\Collection;

class BatchCraftingProcessor
{
    private readonly GlobalEventGoalEligibilityService $globalEventGoalEligibilityService;

    private readonly EventBatchEnchantingAffixSelector $eventBatchEnchantingAffixSelector;

    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly AlchemyService $alchemyService,
        private readonly TrinketCraftingService $trinketCraftingService,
        private readonly EnchantingService $enchantingService,
        private readonly HolyItemService $holyItemService,
        private readonly MultiInventoryActionService $multiInventoryActionService,
        private readonly UseItemService $useItemService,
        private readonly BatchCraftingSetService $batchCraftingSetService,
        private readonly InventorySetService $inventorySetService,
        private readonly HandleUpdatingCraftingGlobalEventGoal $handleUpdatingCraftingGlobalEventGoal,
        private readonly ServerMessageHandler $serverMessageHandler,
        ?GlobalEventGoalEligibilityService $globalEventGoalEligibilityService = null,
        ?EventBatchEnchantingAffixSelector $eventBatchEnchantingAffixSelector = null,
    ) {
        $this->globalEventGoalEligibilityService = $globalEventGoalEligibilityService ?? new GlobalEventGoalEligibilityService();
        $this->eventBatchEnchantingAffixSelector = $eventBatchEnchantingAffixSelector ?? new EventBatchEnchantingAffixSelector();
    }

    public function craftSetQueue(): array
    {
        return $this->craftExperienceTargets(null);
    }

    public function craftEnchantSetPlanKeys(array $queue): array
    {
        $keys = [];
        $ringIndex = 0;

        foreach ($queue as $target) {
            if ($target['type'] === ItemType::RING->value) {
                $keys[] = 'ring_' . $ringIndex;
                $ringIndex++;

                continue;
            }

            $keys[] = $target['type'];
        }

        return $keys;
    }

    public function craftSetPreviewItems(Character $character, array $selectedItemIds = []): array
    {
        $queue = $this->craftSetQueue();
        $keys = $this->craftEnchantSetPlanKeys($queue);

        return collect($queue)
            ->values()
            ->map(function (array $target, int $index) use ($character, $keys, $selectedItemIds): array {
                $key = $keys[$index] ?? (string) $index;
                $candidates = $this->craftableItemCandidatesForTarget($character, $target['type'], $target['crafting_type']);
                $selectedItemId = isset($selectedItemIds[$key]) ? (int) $selectedItemIds[$key] : null;

                return [
                    'key' => $key,
                    'target' => $target,
                    'item' => $this->resolveSelectedOrHighestCraftableItem($candidates, $selectedItemId),
                    'available_items' => $candidates->map(fn ($candidate) => [
                        'id' => $candidate->id,
                        'name' => $candidate->name,
                        'cost' => (int) ($candidate->cost ?? 0),
                    ])->values()->all(),
                ];
            })
            ->all();
    }

    public function craftableItemCandidatesForTarget(Character $character, string $type, string $craftingType): Collection
    {
        try {
            $candidateIds = $this->specificCraftableItems($character, $craftingType)
                ->filter(fn ($item) => $item->type === $type)
                ->pluck('id');
        } catch (\Throwable) {
            return collect();
        }

        if ($candidateIds->isEmpty()) {
            return collect();
        }

        return Item::whereIn('id', $candidateIds)
            ->orderByDesc('skill_level_required')
            ->orderByDesc('cost')
            ->orderBy('id')
            ->get();
    }

    private function resolveSelectedOrHighestCraftableItem(Collection $candidates, ?int $selectedItemId): ?Item
    {
        if (! is_null($selectedItemId)) {
            $selected = $candidates->first(fn ($candidate) => (int) $candidate->id === $selectedItemId);

            if (! is_null($selected)) {
                return $selected;
            }
        }

        return $candidates->first();
    }

    public function isEnchantableSetItem(?Item $item): bool
    {
        return ! is_null($item)
            && ! in_array($item->type, ['trinket', 'artifact', 'alchemy', 'gem', 'quest'], true)
            && is_null($item->item_prefix_id)
            && is_null($item->item_suffix_id);
    }

    public function processOneTick(BatchCrafting $batchCrafting, Character $character): array
    {
        $type = BatchCraftingType::from($batchCrafting->batch_type);
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);

        return match ($type) {
            BatchCraftingType::CRAFT => $this->processCraft($batchCrafting, $character, $disposition),
            BatchCraftingType::CRAFT_AND_ENCHANT => $this->processCraftAndEnchant($batchCrafting, $character, $disposition),
            BatchCraftingType::ENCHANT => $this->processEnchant($batchCrafting, $character, $disposition),
            BatchCraftingType::ALCHEMY => $this->processAlchemy($batchCrafting, $character, $disposition),
            BatchCraftingType::HOLY_OILS => $this->processHolyOils($batchCrafting, $character, $disposition),
            BatchCraftingType::TRINKETRY => $this->processTrinketry($batchCrafting, $character, $disposition),
        };
    }

    private function processCraft(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $craftMode = $progress['craft_mode'] ?? 'experience';

        if ($craftMode === 'event') {
            return $this->processEventCraft($batchCrafting, $character);
        }

        if ($craftMode === 'experience') {
            $itemsPerTick = BatchCraftingService::FULL_SETS_PER_EXPERIENCE_TICK * BatchCraftingService::ITEMS_PER_FULL_SET;
            $requiredCapacity = $this->retainedCraftedItemsSetSlotsForExperience($disposition);

            if ($this->shouldMoveKeptOutputToBatchSet($disposition) && ! $this->batchCraftingSetService->canAccept($character, $requiredCapacity)) {
                return ['end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL];
            }

            return $this->processRepeatedActions($itemsPerTick, function () use ($batchCrafting, $character, $disposition, $progress) {
                return $this->processCraftExperience($batchCrafting->refresh(), $character->refresh(), $disposition, $batchCrafting->refresh()->progress ?? $progress, true);
            });
        }

        if ($craftMode === 'specific_item') {
            if ($this->shouldMoveKeptOutputToBatchSet($disposition) && ! $this->batchCraftingSetService->canAccept($character, 1)) {
                return ['end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL];
            }

            return $this->processCraftSpecificItem($batchCrafting, $character, $disposition, $progress);
        }

        if ($craftMode === 'craft_set') {
            return $this->processRepeatedActions(BatchCraftingService::ITEMS_PER_RECURRING_TICK, function () use ($batchCrafting, $character, $disposition) {
                return $this->processCraftSetSingle($batchCrafting->refresh(), $character->refresh(), $disposition);
            });
        }

        return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
    }

    private function processCraftSetSingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $queue = $progress['craft_set_queue'] ?? [];
        $index = (int) ($progress['craft_set_index'] ?? 0);

        if (empty($queue) || $index >= count($queue)) {
            return ['end_reason' => BatchCraftingEndReason::CRAFT_SET_COMPLETE];
        }

        if ($this->shouldMoveKeptOutputToBatchSet($disposition) && ! $this->batchCraftingSetService->canAccept($character, 1)) {
            return ['end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL];
        }

        $target = $queue[$index];
        $keys = $progress['craft_set_keys'] ?? $this->craftEnchantSetPlanKeys($queue);
        $key = $keys[$index] ?? (string) $index;
        $selectedItemId = $progress['craft_set_selected_item_ids'][$key] ?? null;
        $item = is_null($selectedItemId)
            ? null
            : $this->craftableItemCandidatesForTarget($character, $target['type'], $target['crafting_type'])
                ->first(fn ($candidate) => (int) $candidate->id === (int) $selectedItemId);

        if (is_null($item)) {
            $item = $this->highestCraftableItemForTarget($character, $target['type'], $target['crafting_type']);
        }

        if (is_null($item)) {
            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'craft_set',
                'status' => 'skipped',
                'failure' => 'No craftable item found for type: ' . $target['type'],
            ]]];
        }

        $craftResult = $this->craftingService->craftForBatch($character->refresh(), $item, $target['crafting_type'], $this->shouldMoveKeptOutputToBatchSet($disposition));

        if (! $craftResult['success'] || is_null($craftResult['item'])) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_set',
                'status' => 'failed',
                'failure' => 'Crafting attempt failed. No item was produced.',
            ]]];
        }

        $craftedItem = $craftResult['item'];
        $craftedItemSnapshot = $this->itemDetails($craftedItem, null, false);
        $dispositionResult = $this->applyDispositionForItem($batchCrafting, $character->refresh(), $craftedItem, $disposition);

        $progress = $batchCrafting->fresh()->progress ?? [];
        $progress['craft_set_index'] = $index + 1;
        $progress['craft_set_completed'] = ((int) ($progress['craft_set_completed'] ?? 0)) + 1;
        $progress['craft_set_current_item'] = $craftedItemSnapshot;
        $batchCrafting->update(['progress' => $progress]);

        $result = [
            'counts' => $this->mergeCounts(['crafted_count' => 1], $dispositionResult['counts']),
            'actions' => [array_merge(['action' => 'craft_set', 'crafted_item' => $craftedItemSnapshot], $dispositionResult['details'])],
        ];

        if ($this->shouldMoveKeptOutputToBatchSet($disposition)) {
            $this->commitKeptItemToBatchSet($batchCrafting, $character->refresh(), $result);
        }

        return $result;
    }

    private function processCraftAndEnchant(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $craftMode = $progress['craft_mode'] ?? 'experience';

        if ($craftMode === 'experience') {
            if ($this->isEnchantingSkillMaxed($character) && $this->allCraftingSkillsMaxed($character)) {
                return ['end_reason' => BatchCraftingEndReason::SKILL_MAXED];
            }

            $itemsPerTick = BatchCraftingService::FULL_SETS_PER_EXPERIENCE_TICK * BatchCraftingService::ITEMS_PER_FULL_SET;
            $requiredCapacity = $this->retainedCraftedItemsSetSlotsForExperience($disposition);

            if ($this->shouldMoveKeptOutputToBatchSet($disposition) && ! $this->batchCraftingSetService->canAccept($character, $requiredCapacity)) {
                return ['end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL];
            }

            return $this->processRepeatedActions($itemsPerTick, function () use ($batchCrafting, $character, $disposition, $progress) {
                return $this->processCraftAndEnchantExperience($batchCrafting->refresh(), $character->refresh(), $disposition, $batchCrafting->refresh()->progress ?? $progress);
            });
        }

        if ($craftMode === 'specific_item') {
            if ($this->shouldMoveKeptOutputToBatchSet($disposition) && ! $this->batchCraftingSetService->canAccept($character, 1)) {
                return ['end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL];
            }

            return $this->processCraftAndEnchantSpecificItem($batchCrafting, $character, $disposition, $progress);
        }

        if ($craftMode === 'craft_enchant_set') {
            return $this->processRepeatedActions(BatchCraftingService::ITEMS_PER_RECURRING_TICK, function () use ($batchCrafting, $character, $disposition) {
                return $this->processCraftEnchantSetSingle($batchCrafting->refresh(), $character->refresh(), $disposition);
            });
        }

        return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
    }

    private function processCraftAndEnchantSpecificItem(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $craftAmount = (int) ($progress['craft_amount'] ?? 0);
        $completedSoFar = (int) ($progress['craft_enchant_specific_count'] ?? 0);

        if ($craftAmount > 0 && $completedSoFar >= $craftAmount) {
            return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED];
        }

        if (($progress['craft_enchant_phase'] ?? 'craft') !== 'enchant' && $craftAmount > 0) {
            $remaining = $craftAmount - $completedSoFar;

            $result = $this->processRepeatedActions($remaining * 2, function () use ($batchCrafting, $character, $disposition, $progress) {
                return $this->processCraftAndEnchantSpecificItemSingle($batchCrafting->refresh(), $character->refresh(), $disposition, $batchCrafting->refresh()->progress ?? $progress);
            });

            if (((int) (($batchCrafting->refresh()->progress ?? [])['craft_enchant_specific_count'] ?? 0)) >= $craftAmount && ! isset($result['end_reason'])) {
                $result['end_reason'] = BatchCraftingEndReason::AMOUNT_REACHED;
            }

            return $result;
        }

        return $this->processCraftAndEnchantSpecificItemSingle($batchCrafting, $character, $disposition, $progress);
    }

    private function processCraftAndEnchantSpecificItemSingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $craftAmount = (int) ($progress['craft_amount'] ?? 0);
        $completedSoFar = (int) ($progress['craft_enchant_specific_count'] ?? 0);

        if ($craftAmount > 0 && $completedSoFar >= $craftAmount) {
            return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED];
        }

        if (($progress['craft_enchant_phase'] ?? 'craft') === 'enchant') {
            $result = $this->processCraftAndEnchantEnchantPhase($batchCrafting, $character, $disposition, $progress);

            if (($result['counts']['enchanted_count'] ?? 0) > 0) {
                $updatedProgress = $batchCrafting->fresh()->progress ?? [];
                $updatedProgress['craft_enchant_specific_count'] = $completedSoFar + 1;
                $batchCrafting->update(['progress' => $updatedProgress]);
            }

            return $result;
        }

        if ($this->shouldMoveKeptOutputToBatchSet($disposition) && ! $this->batchCraftingSetService->canAccept($character, 1)) {
            return ['end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL];
        }

        $craftingType = $progress['specific_crafting_type'] ?? null;
        $itemId = (int) ($progress['specific_item_id'] ?? 0);

        if (is_null($craftingType) || $itemId <= 0) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $craftableItems = $this->specificCraftableItems($character, $craftingType);

        $craftableItem = $craftableItems->first(fn ($item) => (int) $item->id === $itemId);
        $item = is_null($craftableItem) ? null : Item::find($craftableItem->id);

        if (is_null($item)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $craftResult = $this->craftingService->craftForBatch($character->refresh(), $item, $craftingType);

        if (! $craftResult['success'] || is_null($craftResult['item'])) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'failed',
                'failure' => 'Crafting attempt failed. No item was produced.',
            ]]];
        }

        $progress['craft_enchant_phase'] = 'enchant';
        $progress['pending_enchant_item_id'] = $craftResult['item']->id;
        $batchCrafting->update(['progress' => $progress]);

        return ['counts' => ['crafted_count' => 1], 'actions' => [[
            'action' => 'craft_and_enchant',
            'status' => 'crafted',
            'crafted_item' => $this->itemDetails($craftResult['item']),
        ]]];
    }

    private function processCraftAndEnchantEnchantPhase(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $pendingItemId = $progress['pending_enchant_item_id'] ?? null;
        $index = (int) ($progress['craft_enchant_index'] ?? 0);

        $progress['craft_enchant_phase'] = 'craft';
        $progress['pending_enchant_item_id'] = null;
        $progress['craft_enchant_index'] = $index + 1;
        $batchCrafting->update(['progress' => $progress]);

        if (is_null($pendingItemId)) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'failed',
                'failure' => 'No pending item to enchant.',
            ]]];
        }

        $item = Item::find($pendingItemId);

        if (is_null($item)) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'failed',
                'failure' => 'Crafted item was removed before enchanting.',
            ]]];
        }

        $craftedSnapshot = $this->itemDetails($item);

        if ($this->intBlockedForEnchant($character, $progress['enchant_affix_ids'] ?? null)) {
            return ['end_reason' => BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING, 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'stopped',
                'crafted_item' => $craftedSnapshot,
                'failure' => 'Your Intelligence is too low for the selected enchantment.',
            ]]];
        }

        $suppressEnchantSuccessMessage = $this->shouldMoveKeptOutputToBatchSet($disposition);
        $enchantAttempt = $this->tryEnchantItem($character, $item, $progress['enchant_affix_ids'] ?? null, $suppressEnchantSuccessMessage);

        if (! $enchantAttempt['attempted']) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'failed',
                'crafted_item' => $craftedSnapshot,
                'failure' => 'Enchanting service did not apply an enchantment.',
            ]]];
        }

        if (is_null($enchantAttempt['item'])) {
            return ['counts' => ['destroyed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'destroyed',
                'destroyed_item' => $this->removedItemDetails($craftedSnapshot, 'destroyed'),
                'failure' => 'The item shattered while enchanting and was destroyed.',
            ]]];
        }

        $enchantedItem = $enchantAttempt['item'];
        $action = [
            'action' => 'craft_and_enchant',
            'crafted_item' => $craftedSnapshot,
            'enchanted_item' => $this->itemDetails($enchantedItem),
            'enchant_affix_name' => implode(', ', $enchantAttempt['affix_names']),
        ];
        $counts = ['enchanted_count' => 1];

        $dispositionResult = $this->applyDispositionForItem($batchCrafting, $character->refresh(), $enchantedItem, $disposition);
        $counts = $this->mergeCounts($counts, $dispositionResult['counts']);
        $action = array_merge($action, $dispositionResult['details']);

        $result = ['counts' => $counts, 'actions' => [$action]];

        if ($this->shouldMoveKeptOutputToBatchSet($disposition)) {
            $this->commitKeptItemToBatchSet($batchCrafting, $character->refresh(), $result);
        }

        return $result;
    }

    private function processEnchant(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];

        if (($progress['event_mode'] ?? false) === true && ($progress['event_action'] ?? null) === 'enchant') {
            return $this->processEventEnchant($batchCrafting, $character);
        }

        if (($progress['enchant_mode'] ?? 'event') === 'set') {
            return $this->processRepeatedActions(BatchCraftingService::ITEMS_PER_RECURRING_TICK, function () use ($batchCrafting, $character) {
                return $this->processEnchantSetSingle($batchCrafting->refresh(), $character->refresh());
            });
        }

        return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
    }

    private function processEnchantSetSingle(BatchCrafting $batchCrafting, Character $character): array
    {
        $progress = $batchCrafting->progress ?? [];
        $set = InventorySet::where('id', $progress['selected_set_id'] ?? 0)->where('character_id', $character->id)->first();

        if (is_null($set) || $set->isBatchCraftingSet()) {
            return ['end_reason' => BatchCraftingEndReason::ENCHANT_SET_COMPLETE];
        }

        $eligibleSlot = $this->pickEnchantableSetSlot($set);

        if (is_null($eligibleSlot)) {
            return ['end_reason' => BatchCraftingEndReason::ENCHANT_SET_COMPLETE];
        }

        if ($character->isInventoryFull()) {
            return ['end_reason' => BatchCraftingEndReason::NO_INVENTORY_SPACE];
        }

        $itemSnapshotBeforeMove = $this->itemDetails($eligibleSlot->item, null, false);
        $progress['enchant_set_current_item'] = $itemSnapshotBeforeMove;
        $batchCrafting->update(['progress' => $progress]);

        if (! $this->inventorySetService->putItemFromInventorySetBackIntoCharacterInventory($character, $set, $eligibleSlot->item)) {
            return ['end_reason' => BatchCraftingEndReason::NO_INVENTORY_SPACE];
        }

        $character = $character->refresh();
        $movedSlot = $character->inventory->slots()->where('item_id', $eligibleSlot->item_id)->orderByDesc('id')->first();

        if (is_null($movedSlot)) {
            $progress['enchant_set_skipped'] = ((int) ($progress['enchant_set_skipped'] ?? 0)) + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'enchant_set',
                'status' => 'skipped',
                'failure' => 'Item could not be moved out of the set for enchanting.',
            ]]];
        }

        if ($this->intBlockedForEnchant($character, $progress['enchant_affix_ids'] ?? null)) {
            $this->inventorySetService->assignItemToSet($set, $movedSlot->refresh());

            return ['end_reason' => BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING, 'actions' => [[
                'action' => 'enchant_set',
                'status' => 'stopped',
                'crafted_item' => $itemSnapshotBeforeMove,
                'failure' => 'Your Intelligence is too low for the selected enchantment.',
            ]]];
        }

        $enchanted = $this->tryEnchantSlot($character, $movedSlot, $progress['enchant_affix_ids'] ?? null);
        $enchantedSnapshot = $enchanted ? $this->itemDetails($movedSlot->refresh()->item, $movedSlot->id, false) : null;

        $character = $character->refresh();
        $this->inventorySetService->assignItemToSet($set, $movedSlot->refresh());

        $progress = $batchCrafting->fresh()->progress ?? [];

        if ($enchanted) {
            $progress['enchant_set_completed'] = ((int) ($progress['enchant_set_completed'] ?? 0)) + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['enchanted_count' => 1], 'actions' => [[
                'action' => 'enchant_set',
                'status' => 'enchanted',
                'crafted_item' => $itemSnapshotBeforeMove,
                'enchanted_item' => $enchantedSnapshot,
            ]]];
        }

        $progress['enchant_set_skipped'] = ((int) ($progress['enchant_set_skipped'] ?? 0)) + 1;
        $batchCrafting->update(['progress' => $progress]);

        return ['counts' => ['skipped_count' => 1], 'actions' => [[
            'action' => 'enchant_set',
            'status' => 'skipped',
            'crafted_item' => $itemSnapshotBeforeMove,
            'failure' => 'Enchanting service did not apply an enchantment.',
        ]]];
    }

    private function pickEnchantableSetSlot(InventorySet $set): ?SetSlot
    {
        return $set->slots()->with('item')->get()->first(function (SetSlot $slot) {
            return $this->isEnchantableSetItem($slot->item);
        });
    }

    private function processAlchemy(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $alchemyMode = $progress['alchemy_mode'] ?? 'experience';

        if ($alchemyMode === 'experience') {
            $alchemySkill = $character->skills()
                ->whereHas('baseSkill', fn ($q) => $q->where('type', \App\Game\Skills\Values\SkillTypeValue::ALCHEMY->value))
                ->with('baseSkill')
                ->first();

            if (! is_null($alchemySkill) && $alchemySkill->level >= $alchemySkill->max_level) {
                return ['end_reason' => BatchCraftingEndReason::SKILL_MAXED];
            }
        }

        if ($alchemyMode === 'amount') {
            $craftAmount = (int) ($progress['alchemy_amount'] ?? $progress['craft_amount'] ?? 0);
            $craftedSoFar = (int) ($progress['alchemy_amount_count'] ?? 0);

            if ($craftAmount > 0 && $craftedSoFar >= $craftAmount) {
                return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED];
            }

            if ($craftAmount > 0) {
                $result = $this->processRepeatedActions($craftAmount - $craftedSoFar, function () use ($batchCrafting, $character, $disposition) {
                    return $this->processAlchemySingle($batchCrafting->refresh(), $character->refresh(), $disposition);
                });

                if (((int) (($batchCrafting->refresh()->progress ?? [])['alchemy_amount_count'] ?? 0)) >= $craftAmount && ! isset($result['end_reason'])) {
                    $result['end_reason'] = BatchCraftingEndReason::AMOUNT_REACHED;
                }

                $this->sendAlchemyUseNowAggregateMessages($character, $disposition, $result['actions'] ?? []);

                return $result;
            }
        } else {
            $result = $this->processRepeatedActions(BatchCraftingService::ITEMS_PER_RECURRING_TICK, function () use ($batchCrafting, $character, $disposition) {
                return $this->processAlchemySingle($batchCrafting->refresh(), $character->refresh(), $disposition);
            });

            $this->sendAlchemyUseNowAggregateMessages($character, $disposition, $result['actions'] ?? []);

            return $result;
        }

        $result = $this->processAlchemySingle($batchCrafting, $character, $disposition);

        $this->sendAlchemyUseNowAggregateMessages($character, $disposition, $result['actions'] ?? []);

        return $result;
    }

    private function processAlchemySingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $alchemyMode = $progress['alchemy_mode'] ?? 'experience';

        if ($alchemyMode === 'experience') {
            $alchemySkill = $character->skills()
                ->whereHas('baseSkill', fn ($q) => $q->where('type', SkillTypeValue::ALCHEMY->value))
                ->with('baseSkill')
                ->first();

            if (! is_null($alchemySkill) && $alchemySkill->level >= $alchemySkill->max_level) {
                return ['end_reason' => BatchCraftingEndReason::SKILL_MAXED];
            }
        }

        if ($alchemyMode === 'amount') {
            $craftAmount = (int) ($progress['alchemy_amount'] ?? $progress['craft_amount'] ?? 0);
            $craftedSoFar = (int) ($progress['alchemy_amount_count'] ?? 0);

            if ($craftAmount > 0 && $craftedSoFar >= $craftAmount) {
                return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED];
            }
        }

        $items = $this->alchemyService->fetchAlchemistItems($character, false);

        if ($items->isEmpty()) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        if ($alchemyMode === 'amount') {
            $alchemyItemId = (int) ($progress['alchemy_item_id'] ?? 0);
            $item = $items->first(fn ($alchemyItem) => (int) $alchemyItem->id === $alchemyItemId);

            if (is_null($item)) {
                return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
            }
        } else {
            $item = $this->xpEligibleItemFromCandidates($items, $alchemySkill?->level);

            if (is_null($item)) {
                return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
            }
        }

        $sourceItem = Item::find($item->id);
        $requiresBagCapacity = $this->alchemyDispositionRequiresBagCapacity($batchCrafting, $character, $disposition, $sourceItem);

        if ($requiresBagCapacity && ! $character->canAddToAlchemyBag(1)) {
            return ['end_reason' => BatchCraftingEndReason::ALCHEMY_BAG_FULL, 'actions' => [[
                'action' => 'alchemy',
                'status' => 'failed',
                'failure' => 'Your Alchemy Bag is full. Empty space before starting this batch.',
            ]]];
        }

        $slotAmountsBefore = AlchemyBagSlot::where('character_id', $character->id)
            ->pluck('amount', 'item_id')
            ->all();
        $currencyBefore = [
            'gold' => $character->gold,
            'gold_dust' => $character->gold_dust,
            'shards' => $character->shards,
        ];

        $this->alchemyService->transmute($character, $item->id, true, ! $requiresBagCapacity);
        $character = $character->refresh();
        $producedSlot = $this->findProducedAlchemySlot($character, $slotAmountsBefore);

        if (is_null($producedSlot)) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'alchemy',
                'alchemy_item' => $this->itemDetails(Item::find($item->id)),
                'failure' => 'Alchemy service did not produce an alchemy bag item.',
                'currency' => $this->currencyDetails($currencyBefore, $character),
            ]]];
        }

        $dispositionResult = $this->applyAlchemyDisposition($batchCrafting, $character, $producedSlot, $disposition);

        if ($alchemyMode === 'amount') {
            $progress = $batchCrafting->fresh()->progress ?? [];
            $progress['alchemy_amount_count'] = ((int) ($progress['alchemy_amount_count'] ?? 0)) + 1;
            $batchCrafting->update(['progress' => $progress]);
        }

        return ['counts' => array_merge(['crafted_count' => 1], $dispositionResult['counts']), 'actions' => [[
            'action' => 'alchemy',
            'alchemy_item' => $this->itemDetails($producedSlot->item, $producedSlot->id, false),
            'currency' => $this->currencyDetails($currencyBefore, $character->refresh()),
        ] + $dispositionResult['details']]];
    }

    private function applyAlchemyDisposition(BatchCrafting $batchCrafting, Character $character, AlchemyBagSlot $slot, BatchCraftingDisposition $disposition): array
    {
        return match ($disposition) {
            BatchCraftingDisposition::KEEP => $this->sendAlchemyKeptInBagMessage($character, $slot, [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false)],
            ]),
            BatchCraftingDisposition::KEEP_HIGHEST => $this->applyAlchemyKeepHighest($batchCrafting, $character, $slot),
            BatchCraftingDisposition::SELL => $this->sellAlchemySlot($character, $slot),
            BatchCraftingDisposition::DESTROY => $this->destroyAlchemySlot($slot),
            BatchCraftingDisposition::LIST => $this->listAlchemySlot($batchCrafting, $character, $slot),
            BatchCraftingDisposition::DISENCHANT => $this->sendAlchemyKeptInBagMessage($character, $slot, [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false, $slot->id)],
            ]),
            BatchCraftingDisposition::KEEP_BEST_SELL_REST => $this->applyAlchemyKeepBestAndRestSlot($batchCrafting, $character, $slot, 'sell'),
            BatchCraftingDisposition::KEEP_BEST_DESTROY_REST => $this->applyAlchemyKeepBestAndRestSlot($batchCrafting, $character, $slot, 'destroy'),
            BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST => $this->sendAlchemyKeptInBagMessage($character, $slot, [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false, $slot->id)],
            ]),
            BatchCraftingDisposition::USE_NOW => $this->useAlchemySlotNow($character, $slot),
        };
    }

    /**
     * Emits the linked "Kept: X in your Alchemy Bag." Server Message for a
     * disposition result that retains the produced item in the given slot,
     * then returns that same result unchanged.
     */
    private function sendAlchemyKeptInBagMessage(Character $character, AlchemyBagSlot $slot, array $result): array
    {
        $itemName = $slot->item->affix_name ?? $slot->item->name;

        $this->serverMessageHandler->sendBasicMessageWithLink(
            $character->user,
            'Kept: ' . $itemName . ' in your Alchemy Bag.',
            $slot->id,
            'alchemy_bag',
            $itemName,
        );

        return $result;
    }

    /**
     * Alchemy Bag capacity is only a real blocker for dispositions that retain
     * the produced item in the bag. Destroy/List consume the output immediately,
     * so they never need bag space. Keep Best Destroy Rest only needs space for
     * its first (retained) item; once a winner is already tracked, each new
     * candidate is either swapped in (freeing the old slot) or destroyed
     * immediately, so no extra capacity is required. Use Now only needs bag
     * space for outputs it cannot use on the character (kingdom bombs,
     * item-targeted alchemy items); usable boon items are used immediately.
     */
    private function alchemyDispositionRequiresBagCapacity(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, ?Item $item): bool
    {
        return match ($disposition) {
            BatchCraftingDisposition::DESTROY, BatchCraftingDisposition::LIST => false,
            BatchCraftingDisposition::KEEP_BEST_SELL_REST, BatchCraftingDisposition::KEEP_BEST_DESTROY_REST => $this->alchemyKeepBestHasNoTrackedSlotYet($batchCrafting, $character),
            BatchCraftingDisposition::USE_NOW => is_null($item) || ! $this->useItemService->isAlchemyBoonItem($item),
            default => true,
        };
    }

    private function alchemyKeepBestHasNoTrackedSlotYet(BatchCrafting $batchCrafting, Character $character): bool
    {
        $progress = $batchCrafting->progress ?? [];
        $trackedSlotId = $progress['alchemy_keep_best_slot'] ?? null;

        if (is_null($trackedSlotId)) {
            return true;
        }

        $trackedSlot = AlchemyBagSlot::where('character_id', $character->id)->find($trackedSlotId);

        return is_null($trackedSlot) || is_null($trackedSlot->item);
    }

    /**
     * Alchemy equivalent of applyKeepBestAndRestItem, operating on AlchemyBagSlot
     * stacks instead of freshly crafted Items. Disenchant is not a valid Alchemy
     * loser action (alchemy items are never enchanted), so only sell/destroy apply.
     */
    private function applyAlchemyKeepBestAndRestSlot(BatchCrafting $batchCrafting, Character $character, AlchemyBagSlot $slot, string $loserAction): array
    {
        $progress = $batchCrafting->progress ?? [];
        $trackedSlotId = $progress['alchemy_keep_best_slot'] ?? null;
        $trackedSlot = is_null($trackedSlotId) ? null : AlchemyBagSlot::where('character_id', $character->id)->find($trackedSlotId);

        if (is_null($trackedSlot) || is_null($trackedSlot->item)) {
            $batchCrafting->update(['progress' => array_merge($progress, ['alchemy_keep_best_slot' => $slot->id])]);

            return $this->sendAlchemyKeptInBagMessage($character, $slot, [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_best_' . $loserAction . '_rest', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false)],
            ]);
        }

        $newLevel = (int) ($slot->item->skill_level_required ?? 0);
        $trackedLevel = (int) ($trackedSlot->item->skill_level_required ?? 0);

        if ($newLevel >= $trackedLevel) {
            $loserResult = $loserAction === 'destroy'
                ? $this->destroyAlchemySlot($trackedSlot)
                : $this->sellAlchemySlot($character, $trackedSlot);
            $batchCrafting->update(['progress' => array_merge($progress, ['alchemy_keep_best_slot' => $slot->id])]);

            return $this->sendAlchemyKeptInBagMessage($character, $slot, [
                'counts' => $this->mergeCounts(['kept_count' => 1], $loserResult['counts']),
                'details' => array_merge(['disposition' => 'keep_best_' . $loserAction . '_rest', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false)], $loserResult['details']),
            ]);
        }

        $loserResult = $loserAction === 'destroy'
            ? $this->destroyAlchemySlot($slot)
            : $this->sellAlchemySlot($character, $slot);

        return [
            'counts' => $loserResult['counts'],
            'details' => array_merge(['disposition' => 'keep_best_' . $loserAction . '_rest'], $loserResult['details']),
        ];
    }

    /**
     * Use Now only uses alchemy items that are valid character boons (not kingdom
     * bombs, not kingdom/item-targeted alchemy items). Anything else, and anything
     * blocked by the existing 10-boon/max-duration limit, stays in the Alchemy Bag.
     */
    private function useAlchemySlotNow(Character $character, AlchemyBagSlot $slot): array
    {
        $itemDetails = $this->itemDetails($slot->item, $slot->id, false);

        if (! $this->useItemService->isAlchemyBoonItem($slot->item)) {
            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $itemDetails],
            ];
        }

        $result = $this->useItemService->useSingleAlchemyItem($character, $slot);

        if (($result['status'] ?? 0) !== 200) {
            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $itemDetails],
            ];
        }

        return [
            'counts' => ['used_count' => 1],
            'details' => ['disposition' => 'use_now', 'used_item' => $this->removedItemDetails($itemDetails, 'used')],
        ];
    }

    /**
     * Sends one Server Message per alchemy item name summarizing how many were
     * used on the character and how many were kept in the Alchemy Bag this tick,
     * instead of one message per individual item processed.
     */
    private function sendAlchemyUseNowAggregateMessages(Character $character, BatchCraftingDisposition $disposition, array $actions): void
    {
        if ($disposition !== BatchCraftingDisposition::USE_NOW) {
            return;
        }

        $summaries = [];

        foreach ($actions as $action) {
            if (($action['action'] ?? null) !== 'alchemy') {
                continue;
            }

            $usedItemName = $action['used_item']['name'] ?? null;
            $keptItemName = $action['kept_item']['name'] ?? null;

            if (! is_null($usedItemName)) {
                $summaries[$usedItemName]['used'] = ($summaries[$usedItemName]['used'] ?? 0) + 1;
                $summaries[$usedItemName]['kept'] = $summaries[$usedItemName]['kept'] ?? 0;
                $summaries[$usedItemName]['slot_id'] = $summaries[$usedItemName]['slot_id'] ?? null;

                continue;
            }

            if (! is_null($keptItemName)) {
                $summaries[$keptItemName]['kept'] = ($summaries[$keptItemName]['kept'] ?? 0) + 1;
                $summaries[$keptItemName]['used'] = $summaries[$keptItemName]['used'] ?? 0;
                $summaries[$keptItemName]['slot_id'] = $action['kept_item']['slot_id'] ?? null;
            }
        }

        foreach ($summaries as $itemName => $summary) {
            $message = $this->buildAlchemyUseNowAggregateMessage($itemName, $summary['used'], $summary['kept']);

            if ($summary['kept'] > 0 && ! is_null($summary['slot_id'] ?? null)) {
                $this->serverMessageHandler->sendBasicMessageWithLink(
                    $character->user,
                    $message,
                    $summary['slot_id'],
                    'alchemy_bag',
                    $itemName,
                );

                continue;
            }

            $this->serverMessageHandler->sendBasicMessage($character->user, $message);
        }
    }

    private function buildAlchemyUseNowAggregateMessage(string $itemName, int $usedCount, int $keptCount): string
    {
        if ($usedCount > 0 && $keptCount > 0) {
            return 'Used ' . $usedCount . ' ' . $itemName . ' ' . ($usedCount === 1 ? 'boon' : 'boons') . ' on you. Kept ' . $keptCount . ' extra ' . $itemName . ' in your Alchemy Bag.';
        }

        if ($usedCount > 0) {
            return 'Used ' . $usedCount . ' ' . $itemName . ' ' . ($usedCount === 1 ? 'boon' : 'boons') . ' on you.';
        }

        return 'Kept ' . $keptCount . ' ' . $itemName . ' in your Alchemy Bag because it could not be used on you right now.';
    }

    private function applyAlchemyKeepHighest(BatchCrafting $batchCrafting, Character $character, AlchemyBagSlot $slot): array
    {
        $progress = $batchCrafting->progress ?? [];
        $trackedSlotId = $progress['alchemy_keep_highest_slot'] ?? null;

        if (is_null($trackedSlotId)) {
            $batchCrafting->update(['progress' => array_merge($progress, ['alchemy_keep_highest_slot' => $slot->id])]);

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false)],
            ];
        }

        $trackedSlot = AlchemyBagSlot::where('character_id', $character->id)->find($trackedSlotId);

        if (is_null($trackedSlot) || is_null($trackedSlot->item)) {
            $batchCrafting->update(['progress' => array_merge($progress, ['alchemy_keep_highest_slot' => $slot->id])]);

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false)],
            ];
        }

        $newLevel = (int) ($slot->item->skill_level_required ?? 0);
        $trackedLevel = (int) ($trackedSlot->item->skill_level_required ?? 0);

        if ($newLevel >= $trackedLevel) {
            $sold = $this->sellAlchemySlot($character, $trackedSlot);
            $batchCrafting->update(['progress' => array_merge($progress, ['alchemy_keep_highest_slot' => $slot->id])]);

            return [
                'counts' => ['kept_count' => 1, 'sold_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false), 'sold_item' => $sold['details']['sold_item'] ?? null, 'gold_gained' => $sold['details']['gold_gained'] ?? 0],
            ];
        }

        $sold = $this->sellAlchemySlot($character, $slot);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'keep_highest', 'sold_item' => $sold['details']['sold_item'] ?? null, 'gold_gained' => $sold['details']['gold_gained'] ?? 0],
        ];
    }

    private function processHolyOils(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];

        if (($progress['holy_oil_mode'] ?? 'selected') === 'set') {
            return $this->processRepeatedActions(BatchCraftingService::ITEMS_PER_RECURRING_TICK, function () use ($batchCrafting, $character, $disposition) {
                return $this->processHolyOilsSetSingle($batchCrafting->refresh(), $character->refresh(), $disposition);
            });
        }

        $requestedApplications = max(1, count($batchCrafting->selected_items ?? []) * count($batchCrafting->selected_oils ?? []));
        $progress['holy_oil_requested_applications'] = $progress['holy_oil_requested_applications'] ?? $requestedApplications;
        $batchCrafting->update(['progress' => $progress]);

        $result = $this->processRepeatedActions($requestedApplications, function () use ($batchCrafting, $character, $disposition) {
            return $this->processHolyOilSingle($batchCrafting->refresh(), $character->refresh(), $disposition);
        });

        $updatedProgress = $batchCrafting->refresh()->progress ?? [];

        if (($updatedProgress['all_oils_applied'] ?? false) === true && ! isset($result['end_reason'])) {
            $result['end_reason'] = BatchCraftingEndReason::ALL_OILS_APPLIED;
        }

        return $result;
    }

    private function processHolyOilSingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $selectedItems = $batchCrafting->selected_items ?? [];
        $selectedOils = $batchCrafting->selected_oils ?? [];

        $inventory = $character->inventory;

        $selectedItems = array_values(array_filter($selectedItems, function (int $slotId) use ($inventory) {
            if (is_null($inventory)) {
                return false;
            }

            $slot = $inventory->slots()->where('id', $slotId)->first();

            if (is_null($slot) || is_null($slot->item)) {
                return false;
            }

            return ($slot->item->holy_stacks - $slot->item->holy_stacks_applied) > 0;
        }));

        $selectedOils = array_values(array_filter($selectedOils, function (int $oilSlotId) use ($character) {
            return AlchemyBagSlot::where('id', $oilSlotId)
                ->where('character_id', $character->id)
                ->where('amount', '>', 0)
                ->exists();
        }));

        if (empty($selectedItems)) {
            $batchCrafting->update(['selected_items' => $selectedItems, 'selected_oils' => $selectedOils]);

            return ['end_reason' => BatchCraftingEndReason::NO_SELECTED_ITEMS_LEFT];
        }

        if (empty($selectedOils)) {
            $batchCrafting->update(['selected_items' => $selectedItems, 'selected_oils' => $selectedOils]);

            return ['end_reason' => BatchCraftingEndReason::NO_OILS_LEFT];
        }

        $slotId = $selectedItems[0];
        $oilSlotId = $selectedOils[0];

        $targetSlot = is_null($inventory) ? null : $inventory->slots()->where('id', $slotId)->with('item')->first();
        $oilSlot = AlchemyBagSlot::where('id', $oilSlotId)->where('character_id', $character->id)->with('item')->first();
        $oilCost = 0;

        if (! is_null($targetSlot) && ! is_null($targetSlot->item) && ! is_null($oilSlot) && ! is_null($oilSlot->item)) {
            $oilCost = $this->holyItemService->getCost($targetSlot->item, $oilSlot->item);

            if ($oilCost > $character->gold_dust) {
                return ['end_reason' => BatchCraftingEndReason::NO_GOLD_DUST];
            }
        }

        $targetItemSnapshot = is_null($targetSlot) ? null : $this->itemDetails($targetSlot->item, $targetSlot->id);
        $oilItemSnapshot = is_null($oilSlot) ? null : $this->itemDetails($oilSlot->item, $oilSlot->id, false);
        $targetItemId = $targetSlot?->item_id;

        $existingSlotIds = is_null($inventory) ? [] : $inventory->slots()->pluck('id')->all();

        $result = $this->holyItemService->applyOil($character, [
            'inventory_slot_id' => $slotId,
            'alchemy_slot_id' => $oilSlotId,
        ]);

        if (! isset($result['status']) || $result['status'] !== 200) {
            $batchCrafting->update(['selected_items' => $selectedItems, 'selected_oils' => $selectedOils]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'holy_oil',
                'failure' => 'Holy oil application failed.',
                'oil_application' => [
                    'target_item' => $targetItemSnapshot,
                    'oil_item' => $oilItemSnapshot,
                    'item_id' => $targetItemId,
                    'oil_slot_id' => $oilSlotId,
                ],
            ]]];
        }

        $character = $character->refresh();

        $originalSlotStillExists = ! is_null($character->inventory)
            && $character->inventory->slots()->where('id', $slotId)->exists();

        $saturatedSlotId = null;

        if (! $originalSlotStillExists) {
            $newSlot = $character->inventory?->slots()->whereNotIn('id', $existingSlotIds)->first();

            if (! is_null($newSlot) && ! is_null($newSlot->item) && ($newSlot->item->holy_stacks - $newSlot->item->holy_stacks_applied) > 0) {
                $newSlotId = $newSlot->id;
                $selectedItems = array_map(fn (int $id) => $id === $slotId ? $newSlotId : $id, $selectedItems);
            } else {
                $saturatedSlotId = $newSlot?->id;
                $selectedItems = array_values(array_filter($selectedItems, fn (int $id) => $id !== $slotId));
            }
        } else {
            $stillEligible = $character->inventory->slots()->where('id', $slotId)
                ->whereHas('item', fn ($query) => $query->whereColumn('holy_stacks', '>', 'holy_stacks_applied'))
                ->exists();

            if (! $stillEligible) {
                $saturatedSlotId = $slotId;
                $selectedItems = array_values(array_filter($selectedItems, fn (int $id) => $id !== $slotId));
            }
        }

        $oilStillExists = AlchemyBagSlot::where('id', $oilSlotId)
            ->where('character_id', $character->id)
            ->where('amount', '>', 0)
            ->exists();

        if (! $oilStillExists) {
            $selectedOils = array_values(array_filter($selectedOils, fn (int $id) => $id !== $oilSlotId));
        }

        $batchCrafting->update(['selected_items' => $selectedItems, 'selected_oils' => $selectedOils]);

        $progress = $batchCrafting->progress ?? [];
        $progress['holy_oil_completed_applications'] = ((int) ($progress['holy_oil_completed_applications'] ?? 0)) + 1;
        $progress['holy_oil_gold_dust_spent'] = ((int) ($progress['holy_oil_gold_dust_spent'] ?? 0)) + $oilCost;
        $progress['holy_oil_current_target_item'] = $targetItemSnapshot;
        $progress['holy_oil_current_oil_item'] = $oilItemSnapshot;

        if (empty($selectedOils)) {
            $progress['all_oils_applied'] = true;
        }

        $batchCrafting->update(['progress' => $progress]);

        $disposalResult = $disposition === BatchCraftingDisposition::KEEP
            ? null
            : $this->disposeHolyOilInventorySlot($batchCrafting, $character, $saturatedSlotId, $disposition);

        return ['counts' => array_merge(['applied_count' => 1], $disposalResult['counts'] ?? []), 'actions' => [array_merge([
            'action' => 'holy_oil',
            'oil_application' => [
                'target_item' => $targetItemSnapshot,
                'oil_item' => $oilItemSnapshot,
                'item_id' => $targetItemId,
                'oil_slot_id' => $oilSlotId,
            ],
        ], $disposalResult['details'] ?? [])]];
    }

    /**
     * Disposition applies once the Holy Oil target item is fully saturated (no
     * remaining stacks to apply), not after every individual oil application, so a
     * partially-oiled item is never sold/destroyed/listed/disenchanted mid-stack.
     */
    private function disposeHolyOilInventorySlot(BatchCrafting $batchCrafting, Character $character, ?int $slotId, BatchCraftingDisposition $disposition): ?array
    {
        if (is_null($slotId)) {
            return null;
        }

        $itemDetails = $this->itemDetails($character->inventory?->slots()->where('id', $slotId)->with('item')->first()?->item, $slotId);

        return match ($disposition) {
            BatchCraftingDisposition::SELL => $this->sellHolyOilInventorySlot($character, $slotId, $itemDetails),
            BatchCraftingDisposition::DESTROY => $this->destroyHolyOilInventorySlot($character, $slotId, $itemDetails),
            BatchCraftingDisposition::DISENCHANT => $this->disenchantHolyOilInventorySlot($character, $slotId, $itemDetails),
            BatchCraftingDisposition::LIST => $this->listHolyOilInventorySlot($batchCrafting, $character, $slotId, $itemDetails),
            default => null,
        };
    }

    private function sellHolyOilInventorySlot(Character $character, int $slotId, ?array $itemDetails): array
    {
        $goldBefore = $character->gold;
        $this->multiInventoryActionService->sellManyItems($character, [$slotId]);
        $goldGained = max(0, $character->refresh()->gold - $goldBefore);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'sell', 'sold_item' => $this->removedItemDetails($itemDetails, 'sold'), 'gold_gained' => $goldGained],
        ];
    }

    private function destroyHolyOilInventorySlot(Character $character, int $slotId, ?array $itemDetails): array
    {
        $this->multiInventoryActionService->destroyManyItems($character, [$slotId]);

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Destroyed: ' . ($itemDetails['name'] ?? 'item') . '.');

        return [
            'counts' => ['destroyed_count' => 1],
            'details' => ['disposition' => 'destroy', 'destroyed_item' => $this->removedItemDetails($itemDetails, 'destroyed')],
        ];
    }

    private function disenchantHolyOilInventorySlot(Character $character, int $slotId, ?array $itemDetails): array
    {
        $goldDustBefore = $character->gold_dust;
        $this->multiInventoryActionService->disenchantManyItems($character, [$slotId]);
        $goldDustGained = max(0, $character->refresh()->gold_dust - $goldDustBefore);

        return [
            'counts' => ['disenchanted_count' => 1],
            'details' => ['disposition' => 'disenchant', 'disenchanted_item' => $this->removedItemDetails($itemDetails, 'disenchanted'), 'gold_dust_gained' => $goldDustGained],
        ];
    }

    private function listHolyOilInventorySlot(BatchCrafting $batchCrafting, Character $character, int $slotId, ?array $itemDetails): array
    {
        $slot = $character->inventory?->slots()->where('id', $slotId)->with('item')->first();

        if (is_null($slot) || is_null($slot->item)) {
            return ['counts' => ['failed_count' => 1], 'details' => ['disposition' => 'list', 'failure' => 'Item could not be listed.']];
        }

        $requestedPrice = (int) (($batchCrafting->progress ?? [])['listing_price'] ?? 1);
        $minPrice = (int) SellItemCalculator::fetchMinPrice($slot->item);
        $price = max($requestedPrice, $minPrice, 1);

        MarketBoard::create([
            'character_id' => $character->id,
            'item_id' => $slot->item_id,
            'listed_price' => $price,
        ]);

        $slot->delete();

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Listed: ' . ($itemDetails['name'] ?? 'item') . ' for: ' . number_format($price) . ' Gold.');

        return [
            'counts' => ['listed_count' => 1],
            'details' => ['disposition' => 'list', 'listed_item' => $this->removedItemDetails($itemDetails, 'listed'), 'listed_price' => $price],
        ];
    }

    private function processHolyOilsSetSingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $set = InventorySet::where('id', $progress['selected_set_id'] ?? 0)->where('character_id', $character->id)->first();

        if (is_null($set) || $set->isBatchCraftingSet()) {
            return ['end_reason' => BatchCraftingEndReason::NO_SELECTED_ITEMS_LEFT];
        }

        $selectedOils = array_values(array_filter($batchCrafting->selected_oils ?? [], function (int $oilSlotId) use ($character) {
            return AlchemyBagSlot::where('id', $oilSlotId)
                ->where('character_id', $character->id)
                ->where('amount', '>', 0)
                ->exists();
        }));

        if (empty($selectedOils)) {
            $batchCrafting->update(['selected_oils' => $selectedOils]);

            return ['end_reason' => BatchCraftingEndReason::NO_OILS_LEFT];
        }

        $eligibleSlot = $set->slots()->with('item.appliedHolyStacks')->get()->first(function (SetSlot $slot) {
            return ! is_null($slot->item)
                && ! in_array($slot->item->type, ['trinket', 'artifact'], true)
                && ($slot->item->holy_stacks - $slot->item->holy_stacks_applied) > 0;
        });

        if (is_null($eligibleSlot)) {
            $progress['all_oils_applied'] = true;
            $batchCrafting->update(['progress' => $progress]);

            return ['end_reason' => BatchCraftingEndReason::ALL_OILS_APPLIED];
        }

        $oilSlotId = $selectedOils[0];
        $oilSlot = AlchemyBagSlot::where('id', $oilSlotId)->where('character_id', $character->id)->with('item')->first();

        if (is_null($oilSlot) || is_null($oilSlot->item)) {
            $selectedOils = array_values(array_filter($selectedOils, fn (int $id) => $id !== $oilSlotId));
            $batchCrafting->update(['selected_oils' => $selectedOils]);

            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'holy_oil_set',
                'status' => 'skipped',
                'failure' => 'Selected oil is no longer available.',
            ]]];
        }

        $cost = $this->holyItemService->getCost($eligibleSlot->item, $oilSlot->item);

        if ($cost > $character->gold_dust) {
            return ['end_reason' => BatchCraftingEndReason::NO_GOLD_DUST];
        }

        $targetItemSnapshot = $this->itemDetails($eligibleSlot->item, null, false);
        $oilItemSnapshot = $this->itemDetails($oilSlot->item, null, false);

        $character->update(['gold_dust' => $character->gold_dust - $cost]);
        $application = $this->applyHolyOilToSetSlot($eligibleSlot, $oilSlot);
        $newSlot = $application['slot'];

        $oilStillExists = AlchemyBagSlot::where('id', $oilSlotId)
            ->where('character_id', $character->id)
            ->where('amount', '>', 0)
            ->exists();

        if (! $oilStillExists) {
            $selectedOils = array_values(array_filter($selectedOils, fn (int $id) => $id !== $oilSlotId));
            $batchCrafting->update(['selected_oils' => $selectedOils]);
        }

        $progress = $batchCrafting->fresh()->progress ?? [];
        $progress['holy_oil_completed_applications'] = ((int) ($progress['holy_oil_completed_applications'] ?? 0)) + 1;
        $progress['holy_oil_gold_dust_spent'] = ((int) ($progress['holy_oil_gold_dust_spent'] ?? 0)) + $cost;
        $progress['holy_oil_total_stat_bonus_applied'] = ((float) ($progress['holy_oil_total_stat_bonus_applied'] ?? 0)) + $application['stat_increase_bonus'];
        $progress['holy_oil_total_devouring_darkness_bonus_applied'] = ((float) ($progress['holy_oil_total_devouring_darkness_bonus_applied'] ?? 0)) + $application['devouring_darkness_bonus'];
        $progress['holy_oil_current_target_item'] = $targetItemSnapshot;
        $progress['holy_oil_current_oil_item'] = $oilItemSnapshot;
        $batchCrafting->update(['progress' => $progress]);

        $refreshedSlot = $newSlot->fresh('item');
        $stillEligible = ! is_null($refreshedSlot) && ! is_null($refreshedSlot->item)
            && ($refreshedSlot->item->holy_stacks - $refreshedSlot->item->holy_stacks_applied) > 0;
        $saturatedSetSlotId = $stillEligible ? null : $newSlot->id;

        $disposalResult = $disposition === BatchCraftingDisposition::KEEP
            ? null
            : $this->disposeHolyOilSetSlot($batchCrafting, $character, $set, $saturatedSetSlotId, $disposition);

        return ['counts' => array_merge(['applied_count' => 1], $disposalResult['counts'] ?? []), 'actions' => [array_merge([
            'action' => 'holy_oil_set',
            'oil_application' => [
                'target_item' => $targetItemSnapshot,
                'oil_item' => $oilItemSnapshot,
                'set_slot_id' => $newSlot->id,
                'oil_slot_id' => $oilSlotId,
            ],
        ], $disposalResult['details'] ?? [])]];
    }

    private function disposeHolyOilSetSlot(BatchCrafting $batchCrafting, Character $character, InventorySet $set, ?int $setSlotId, BatchCraftingDisposition $disposition): ?array
    {
        if (is_null($setSlotId)) {
            return null;
        }

        $itemDetails = $this->itemDetails($set->slots()->where('id', $setSlotId)->with('item')->first()?->item);

        return match ($disposition) {
            BatchCraftingDisposition::SELL => $this->sellHolyOilSetSlot($character, $set, $setSlotId, $itemDetails),
            BatchCraftingDisposition::DESTROY => $this->destroyHolyOilSetSlot($character, $set, $setSlotId, $itemDetails),
            BatchCraftingDisposition::DISENCHANT => $this->disenchantHolyOilSetSlot($character, $set, $setSlotId, $itemDetails),
            BatchCraftingDisposition::LIST => $this->listHolyOilSetSlot($batchCrafting, $character, $set, $setSlotId, $itemDetails),
            default => null,
        };
    }

    private function sellHolyOilSetSlot(Character $character, InventorySet $set, int $setSlotId, ?array $itemDetails): array
    {
        $goldBefore = $character->gold;
        $this->multiInventoryActionService->sellManySetSlots($character, $set, [$setSlotId]);
        $goldGained = max(0, $character->refresh()->gold - $goldBefore);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'sell', 'sold_item' => $this->removedItemDetails($itemDetails, 'sold'), 'gold_gained' => $goldGained],
        ];
    }

    private function destroyHolyOilSetSlot(Character $character, InventorySet $set, int $setSlotId, ?array $itemDetails): array
    {
        $this->multiInventoryActionService->destroyManySetSlots($character, $set, [$setSlotId]);

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Destroyed: ' . ($itemDetails['name'] ?? 'item') . '.');

        return [
            'counts' => ['destroyed_count' => 1],
            'details' => ['disposition' => 'destroy', 'destroyed_item' => $this->removedItemDetails($itemDetails, 'destroyed')],
        ];
    }

    private function disenchantHolyOilSetSlot(Character $character, InventorySet $set, int $setSlotId, ?array $itemDetails): array
    {
        $goldDustBefore = $character->gold_dust;
        $this->multiInventoryActionService->disenchantManySetSlots($character, $set, [$setSlotId]);
        $goldDustGained = max(0, $character->refresh()->gold_dust - $goldDustBefore);

        return [
            'counts' => ['disenchanted_count' => 1],
            'details' => ['disposition' => 'disenchant', 'disenchanted_item' => $this->removedItemDetails($itemDetails, 'disenchanted'), 'gold_dust_gained' => $goldDustGained],
        ];
    }

    private function listHolyOilSetSlot(BatchCrafting $batchCrafting, Character $character, InventorySet $set, int $setSlotId, ?array $itemDetails): array
    {
        $slot = $set->slots()->where('id', $setSlotId)->with('item')->first();

        if (is_null($slot) || is_null($slot->item)) {
            return ['counts' => ['failed_count' => 1], 'details' => ['disposition' => 'list', 'failure' => 'Item could not be listed.']];
        }

        $requestedPrice = (int) (($batchCrafting->progress ?? [])['listing_price'] ?? 1);
        $minPrice = (int) SellItemCalculator::fetchMinPrice($slot->item);
        $price = max($requestedPrice, $minPrice, 1);

        $this->multiInventoryActionService->listManySetSlots($character, $set, [$setSlotId], $price);

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Listed: ' . ($itemDetails['name'] ?? 'item') . ' for: ' . number_format($price) . ' Gold.');

        return [
            'counts' => ['listed_count' => 1],
            'details' => ['disposition' => 'list', 'listed_item' => $this->removedItemDetails($itemDetails, 'listed'), 'listed_price' => $price],
        ];
    }

    private function applyHolyOilToSetSlot(SetSlot $setSlot, AlchemyBagSlot $oilSlot): array
    {
        $holyItemEffect = new ItemHolyValue($oilSlot->item->holy_level);
        $devouringDarknessBonus = $holyItemEffect->getRandomDevoidanceIncrease();
        $statIncreaseBonus = $holyItemEffect->getRandomStatIncrease() / 100;

        if ($setSlot->item->appliedHolyStacks->isEmpty()) {
            $newItem = $setSlot->item->duplicate();

            $newItem->update([
                'market_sellable' => true,
                'is_mythic' => $setSlot->item->is_mythic,
                'is_cosmic' => $setSlot->item->is_cosmic,
            ]);

            $newItem->appliedHolyStacks()->create([
                'item_id' => $newItem->id,
                'devouring_darkness_bonus' => $devouringDarknessBonus,
                'stat_increase_bonus' => $statIncreaseBonus,
            ]);

            $inventorySetId = $setSlot->inventory_set_id;
            $setSlot->delete();
            $this->decrementAlchemySlot($oilSlot);

            return [
                'slot' => SetSlot::create([
                    'inventory_set_id' => $inventorySetId,
                    'item_id' => $newItem->id,
                ]),
                'devouring_darkness_bonus' => $devouringDarknessBonus,
                'stat_increase_bonus' => $statIncreaseBonus,
            ];
        }

        $this->decrementAlchemySlot($oilSlot);

        $setSlot->item->appliedHolyStacks()->create([
            'item_id' => $setSlot->item_id,
            'devouring_darkness_bonus' => $devouringDarknessBonus,
            'stat_increase_bonus' => $statIncreaseBonus,
        ]);

        return [
            'slot' => $setSlot->refresh(),
            'devouring_darkness_bonus' => $devouringDarknessBonus,
            'stat_increase_bonus' => $statIncreaseBonus,
        ];
    }

    private function processTrinketry(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $requiredCapacity = $disposition === BatchCraftingDisposition::KEEP ? BatchCraftingService::ITEMS_PER_RECURRING_TICK : 1;

        if ($this->shouldMoveKeptOutputToBatchSet($disposition) && ! $this->batchCraftingSetService->canAccept($character, $requiredCapacity)) {
            return ['end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL];
        }

        return $this->processRepeatedActions(BatchCraftingService::ITEMS_PER_RECURRING_TICK, function () use ($batchCrafting, $character, $disposition) {
            return $this->processTrinketrySingle($batchCrafting->refresh(), $character->refresh(), $disposition);
        });
    }

    private function processTrinketrySingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $trinketryMode = $progress['trinketry_mode'] ?? 'experience';

        if ($trinketryMode === 'experience') {
            $skill = $character->skills()
                ->whereHas('baseSkill', fn ($q) => $q->where('name', 'Trinketry'))
                ->with('baseSkill')
                ->first();

            if (! is_null($skill) && $skill->level >= $skill->max_level) {
                return ['end_reason' => BatchCraftingEndReason::SKILL_MAXED];
            }
        }

        $items = $this->trinketCraftingService->fetchItemsToCraft($character, false);

        if (empty($items)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $item = $this->xpEligibleItemFromCandidates(collect($items), $skill?->level);

        if (is_null($item)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $craftResult = $this->trinketCraftingService->craftForBatch($character, $item, $this->shouldMoveKeptOutputToBatchSet($disposition));
        $character = $character->refresh();

        if (! $craftResult['success'] || is_null($craftResult['item'])) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'trinketry',
                'failure' => 'Trinketry service did not produce an item.',
            ]]];
        }

        $craftedItem = $craftResult['item'];
        $dispositionResult = $this->applyDispositionForItem($batchCrafting, $character, $craftedItem, $disposition);
        $counts = $this->mergeCounts(['crafted_count' => 1], $dispositionResult['counts']);
        $actions = [[
            'action' => 'trinketry',
            'trinketry_item' => $this->itemDetails($craftedItem),
        ] + $dispositionResult['details']];

        $result = ['counts' => $counts, 'actions' => $actions];

        if ($this->shouldMoveKeptOutputToBatchSet($disposition)) {
            $this->commitKeptItemToBatchSet($batchCrafting, $character->refresh(), $result);
        }

        return $result;
    }

    private function processCraftExperience(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress, bool $moveKeptOutputToBatchSet = false): array
    {
        $isCraftAndEnchant = $batchCrafting->batch_type === BatchCraftingType::CRAFT_AND_ENCHANT->value;

        if (! $isCraftAndEnchant && $this->allCraftingSkillsMaxed($character)) {
            return ['end_reason' => BatchCraftingEndReason::SKILL_MAXED];
        }

        $queue = $progress['craft_experience_queue'] ?? null;

        if (is_null($queue)) {
            $targets = in_array($batchCrafting->batch_type, [BatchCraftingType::CRAFT->value, BatchCraftingType::CRAFT_AND_ENCHANT->value], true)
                ? $this->nonMaxedCraftExperienceTargets($character)
                : $this->craftExperienceTargets($progress['craft_experience_skill'] ?? null);

            $allCraftingSkillsMaxedFallback = $isCraftAndEnchant && empty($targets);

            if ($allCraftingSkillsMaxedFallback) {
                $targets = $this->craftExperienceTargets(null);
            }

            $queue = $targets;
            $hasEligibleTarget = false;

            foreach ($targets as $targetToCheck) {
                $hasEligibleTarget = $allCraftingSkillsMaxedFallback
                    ? ! is_null($this->craftableItemForTarget($character, $targetToCheck['type'], $targetToCheck['crafting_type']))
                    : ! is_null($this->xpEligibleCraftableItem($character, $targetToCheck['crafting_type'], $targetToCheck['type']));

                if ($hasEligibleTarget) {
                    break;
                }
            }

            $progress['craft_experience_queue'] = $queue;
            $progress['craft_experience_index'] = 0;
            $progress['craft_experience_has_targets'] = $hasEligibleTarget;
            $progress['craft_experience_ignore_xp_eligibility'] = $allCraftingSkillsMaxedFallback;
            $batchCrafting->update(['progress' => $progress]);
        }

        $index = (int) ($progress['craft_experience_index'] ?? 0);

        if (empty($queue)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        if (($progress['craft_experience_has_targets'] ?? true) === false) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        if ($index >= count($queue)) {
            $index = 0;
            $progress['craft_experience_index'] = 0;
            $batchCrafting->update(['progress' => $progress]);
        }

        $ignoreXpEligibility = ($progress['craft_experience_ignore_xp_eligibility'] ?? false) === true;
        $queueLength = count($queue);
        $attemptsRemaining = $queueLength;
        $target = $queue[$index];
        $craftingType = $target['crafting_type'];
        $item = null;

        while ($attemptsRemaining > 0) {
            $target = $queue[$index];
            $craftingType = $target['crafting_type'];
            $item = $ignoreXpEligibility
                ? $this->craftableItemForTarget($character, $target['type'], $craftingType)
                : $this->xpEligibleCraftableItem($character, $craftingType, $target['type']);

            $index = ($index + 1) % $queueLength;
            $attemptsRemaining--;

            if (! is_null($item)) {
                break;
            }
        }

        $progress['craft_experience_index'] = $index;
        $batchCrafting->update(['progress' => $progress]);

        if (is_null($item)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $progress = $batchCrafting->fresh()->progress ?? [];
        $progress['craft_experience_current_item_snapshot'] = $this->itemDetails($item, null, false);
        $progress['craft_experience_current_type'] = $craftingType;
        $batchCrafting->update(['progress' => $progress]);

        $shouldMoveKeptOutputToBatchSet = $moveKeptOutputToBatchSet && $this->shouldMoveKeptOutputToBatchSet($disposition);
        $result = $this->craftItem($batchCrafting, $character, $disposition, $item, $craftingType, $shouldMoveKeptOutputToBatchSet);

        if ($shouldMoveKeptOutputToBatchSet) {
            $this->commitKeptItemToBatchSet($batchCrafting, $character->refresh(), $result);
        }

        return $result;
    }

    private function processCraftAndEnchantExperience(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $result = $this->processCraftExperience($batchCrafting, $character, BatchCraftingDisposition::KEEP, $progress, false);
        $itemId = $result['actions'][0]['kept_item']['item_id'] ?? $result['actions'][0]['crafted_item']['item_id'] ?? null;

        if (! is_null($itemId)) {
            $item = Item::find($itemId);

            if (! is_null($item)) {
                if ($this->intBlockedForEnchant($character->refresh(), $progress['enchant_affix_ids'] ?? null)) {
                    $result['end_reason'] = BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING;
                    $result['actions'][0]['failure'] = 'Your Intelligence is too low for the selected enchantment.';

                    return $result;
                }

                $suppressEnchantSuccessMessage = $this->shouldMoveKeptOutputToBatchSet($disposition);
                $enchantAttempt = $this->tryEnchantItem($character->refresh(), $item, $progress['enchant_affix_ids'] ?? null, $suppressEnchantSuccessMessage);

                if ($enchantAttempt['attempted'] && is_null($enchantAttempt['item'])) {
                    $result['counts'] = $this->mergeCounts($result['counts'] ?? [], ['destroyed_count' => 1]);
                    $result['actions'][0]['status'] = 'destroyed';
                    $result['actions'][0]['destroyed_item'] = $this->removedItemDetails(
                        $result['actions'][0]['kept_item'] ?? $result['actions'][0]['crafted_item'] ?? null,
                        'destroyed'
                    );
                    $result['actions'][0]['failure'] = 'The item shattered while enchanting and was destroyed.';
                } elseif ($enchantAttempt['attempted']) {
                    $enchantedItem = $enchantAttempt['item'];
                    $dispositionResult = $this->applyDispositionForItem($batchCrafting, $character->refresh(), $enchantedItem, $disposition);
                    $result['counts'] = $this->mergeCounts($result['counts'] ?? [], ['enchanted_count' => 1]);
                    $result['counts'] = $this->mergeCounts($result['counts'], $dispositionResult['counts']);
                    $result['actions'][0] = array_merge($result['actions'][0], ['enchanted_item' => $this->itemDetails($enchantedItem), 'enchant_affix_name' => implode(', ', $enchantAttempt['affix_names'])], $dispositionResult['details']);

                    if ($this->shouldMoveKeptOutputToBatchSet($disposition)) {
                        $this->commitKeptItemToBatchSet($batchCrafting, $character->refresh(), $result);
                    }
                } else {
                    $result['counts'] = $this->mergeCounts($result['counts'] ?? [], ['failed_count' => 1]);
                    $result['actions'][0]['failure'] = 'Enchanting service did not apply an enchantment.';
                    $dispositionResult = $this->applyDispositionForItem($batchCrafting, $character->refresh(), $item, $disposition);
                    $result['counts'] = $this->mergeCounts($result['counts'], $dispositionResult['counts']);
                    $result['actions'][0] = array_merge($result['actions'][0], $dispositionResult['details']);

                    if ($this->shouldMoveKeptOutputToBatchSet($disposition)) {
                        $this->commitKeptItemToBatchSet($batchCrafting, $character->refresh(), $result);
                    }
                }
            }
        }

        return $result;
    }

    private function processCraftEnchantSetSingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $phase = $progress['craft_enchant_set_phase'] ?? 'crafting';

        if ($phase === 'crafting') {
            return $this->craftEnchantSetCraftPhaseSingle($batchCrafting, $character);
        }

        if ($phase === 'enchanting') {
            return $this->craftEnchantSetEnchantPhaseSingle($batchCrafting, $character);
        }

        if ($phase === 'finalizing') {
            return $this->craftEnchantSetFinalizePhaseSingle($batchCrafting, $character, $disposition);
        }

        return ['end_reason' => BatchCraftingEndReason::CRAFT_ENCHANT_SET_COMPLETE];
    }

    private function craftEnchantSetEnchantExistingPhaseSingle(BatchCrafting $batchCrafting, Character $character): array
    {
        $progress = $batchCrafting->progress ?? [];
        $queue = $progress['craft_enchant_set_queue'] ?? [];
        $keys = $progress['craft_enchant_set_keys'] ?? [];
        $index = (int) ($progress['craft_enchant_set_enchant_index'] ?? 0);

        if ($index >= count($queue)) {
            return ['end_reason' => BatchCraftingEndReason::CRAFT_ENCHANT_SET_COMPLETE];
        }

        $set = InventorySet::where('id', $progress['selected_set_id'] ?? 0)->where('character_id', $character->id)->first();

        if (is_null($set) || $set->isBatchCraftingSet() || $set->is_equipped) {
            return ['end_reason' => BatchCraftingEndReason::CRAFT_ENCHANT_SET_TARGET_SET_CHANGED];
        }

        $key = $keys[$index] ?? (string) $index;
        $setSlotId = $progress['craft_enchant_set_existing_slot_ids'][$key] ?? null;

        if (is_null($setSlotId)) {
            $progress['craft_enchant_set_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => []];
        }

        $setSlot = SetSlot::find($setSlotId);

        if (is_null($setSlot) || is_null($setSlot->item)) {
            $progress['craft_enchant_set_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'skipped',
                'failure' => 'The item is no longer in the target set.',
            ]]];
        }

        if ($setSlot->item->is_unique || $setSlot->item->is_mythic || $setSlot->item->is_cosmic) {
            $progress['craft_enchant_set_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'skipped',
                'crafted_item' => $this->itemDetails($setSlot->item, null, false),
                'failure' => 'Uniques, Mythics and Cosmic items are never touched by batch enchanting.',
            ]]];
        }

        $beforeSnapshot = $this->itemDetails($setSlot->item, null, false);
        $plan = $progress['enchant_plan'][$key] ?? [];
        $prefixId = $plan['prefix_affix_id'] ?? null;
        $suffixId = $plan['suffix_affix_id'] ?? null;
        $prefixAffix = is_null($prefixId) ? null : ItemAffix::find($prefixId);
        $suffixAffix = is_null($suffixId) ? null : ItemAffix::find($suffixId);
        $affixIds = array_values(array_filter([$prefixId, $suffixId], fn ($affixId) => ! is_null($affixId)));

        if (empty($affixIds)) {
            $progress['craft_enchant_set_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'failed',
                'crafted_item' => $beforeSnapshot,
                'failure' => 'This item has no prefix or suffix selected to enchant.',
            ]]];
        }

        if ($this->intBlockedForEnchant($character, $affixIds)) {
            return ['end_reason' => BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING, 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'stopped',
                'crafted_item' => $beforeSnapshot,
                'prefix_affix_name' => $prefixAffix?->name,
                'suffix_affix_name' => $suffixAffix?->name,
                'failure' => 'Your Intelligence is too low for the selected enchantment.',
            ]]];
        }

        if ($character->isInventoryFull()) {
            return ['end_reason' => BatchCraftingEndReason::NO_INVENTORY_SPACE];
        }

        if (! $this->inventorySetService->putItemFromInventorySetBackIntoCharacterInventory($character, $set, $setSlot->item)) {
            return ['end_reason' => BatchCraftingEndReason::NO_INVENTORY_SPACE];
        }

        $character = $character->refresh();
        $movedSlot = $character->inventory->slots()->where('item_id', $setSlot->item_id)->orderByDesc('id')->first();

        if (is_null($movedSlot)) {
            $progress['craft_enchant_set_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'skipped',
                'failure' => 'Item could not be moved out of the set for enchanting.',
            ]]];
        }

        $goldBeforeEnchant = $character->gold;
        $attempted = $this->tryEnchantSlot($character, $movedSlot, $affixIds);
        $goldSpent = max(0, $goldBeforeEnchant - $character->refresh()->gold);
        $survivingSlot = $attempted ? InventorySlot::find($movedSlot->id) : $movedSlot;

        $progress = $batchCrafting->fresh()->progress ?? [];
        $progress['craft_enchant_set_enchant_index'] = $index + 1;
        $progress['craft_enchant_set_completed_work_units'] = ((int) ($progress['craft_enchant_set_completed_work_units'] ?? 0)) + 1;

        if ($attempted && is_null($survivingSlot)) {
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['destroyed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'destroyed',
                'destroyed_item' => $this->removedItemDetails($beforeSnapshot, 'destroyed'),
                'prefix_affix_name' => $prefixAffix?->name,
                'suffix_affix_name' => $suffixAffix?->name,
                'gold_spent' => $goldSpent,
                'failure' => 'The item shattered while enchanting and was destroyed. It could not be returned to the set.',
            ]]];
        }

        $character = $character->refresh();
        $this->inventorySetService->assignItemToSet($set, $survivingSlot->refresh());

        $afterItem = $survivingSlot->refresh()->item;
        $prefixApplied = $attempted && ! is_null($prefixId) && $afterItem->item_prefix_id === $prefixId;
        $suffixApplied = $attempted && ! is_null($suffixId) && $afterItem->item_suffix_id === $suffixId;
        $enchantedSnapshot = $this->itemDetails($afterItem, null, false);

        if ($prefixApplied) {
            $progress['craft_enchant_set_prefix_applied_count'] = ((int) ($progress['craft_enchant_set_prefix_applied_count'] ?? 0)) + 1;
        }

        if ($suffixApplied) {
            $progress['craft_enchant_set_suffix_applied_count'] = ((int) ($progress['craft_enchant_set_suffix_applied_count'] ?? 0)) + 1;
        }

        $progress['craft_enchant_set_current_item'] = $enchantedSnapshot;
        $batchCrafting->update(['progress' => $progress]);

        if (! $prefixApplied && ! $suffixApplied) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'failed',
                'crafted_item' => $beforeSnapshot,
                'prefix_affix_name' => $prefixAffix?->name,
                'suffix_affix_name' => $suffixAffix?->name,
                'gold_spent' => $goldSpent,
                'failure' => 'Enchanting service did not apply either enchantment.',
            ]]];
        }

        return ['counts' => ['enchanted_count' => 1], 'actions' => [[
            'action' => 'craft_enchant_set_enchant',
            'phase' => 'enchanting',
            'status' => $prefixApplied && $suffixApplied ? 'double_enchanted' : 'enchanted',
            'crafted_item' => $beforeSnapshot,
            'enchanted_item' => $enchantedSnapshot,
            'prefix_affix_name' => $prefixAffix?->name,
            'suffix_affix_name' => $suffixAffix?->name,
            'prefix_applied' => $prefixApplied,
            'suffix_applied' => $suffixApplied,
            'gold_spent' => $goldSpent,
        ]]];
    }

    private function craftEnchantSetCraftPhaseSingle(BatchCrafting $batchCrafting, Character $character): array
    {
        $progress = $batchCrafting->progress ?? [];
        $queue = $progress['craft_enchant_set_queue'] ?? [];
        $keys = $progress['craft_enchant_set_keys'] ?? [];
        $index = (int) ($progress['craft_enchant_set_craft_index'] ?? 0);

        if ($index >= count($queue)) {
            $progress['craft_enchant_set_phase'] = 'enchanting';
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => []];
        }

        $target = $queue[$index];
        $key = $keys[$index] ?? (string) $index;

        $selectedItemId = $progress['craft_enchant_set_selected_item_ids'][$key] ?? null;
        $item = is_null($selectedItemId)
            ? $this->highestCraftableItemForTarget($character, $target['type'], $target['crafting_type'])
            : Item::find($selectedItemId);

        if (is_null($item)) {
            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_craft',
                'phase' => 'crafting',
                'status' => 'skipped',
                'failure' => 'No craftable item found for type: ' . $target['type'],
            ]]];
        }

        $goldBeforeCraft = $character->gold;
        $craftResult = $this->craftingService->craftForBatch($character->refresh(), $item, $target['crafting_type']);

        if (! $craftResult['success'] || is_null($craftResult['item'])) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_craft',
                'phase' => 'crafting',
                'status' => 'failed',
                'failure' => 'Crafting attempt failed. No item was produced.',
            ]]];
        }

        $craftedItem = $craftResult['item'];
        $craftedItemSnapshot = $this->itemDetails($craftedItem, null, false);
        $goldSpent = max(0, $goldBeforeCraft - $character->refresh()->gold);

        $progress = $batchCrafting->fresh()->progress ?? [];
        $progress['craft_enchant_set_craft_index'] = $index + 1;
        $progress['craft_enchant_set_completed_work_units'] = ((int) ($progress['craft_enchant_set_completed_work_units'] ?? 0)) + 1;
        $progress['craft_enchant_set_crafted_item_ids'][$key] = $craftedItem->id;
        $progress['craft_enchant_set_current_item'] = $craftedItemSnapshot;
        $batchCrafting->update(['progress' => $progress]);

        return ['counts' => ['crafted_count' => 1], 'actions' => [[
            'action' => 'craft_enchant_set_craft',
            'phase' => 'crafting',
            'status' => 'crafted',
            'crafted_item' => $craftedItemSnapshot,
            'gold_spent' => $goldSpent,
        ]]];
    }

    private function craftEnchantSetEnchantPhaseSingle(BatchCrafting $batchCrafting, Character $character): array
    {
        $progress = $batchCrafting->progress ?? [];
        $queue = $progress['craft_enchant_set_queue'] ?? [];
        $keys = $progress['craft_enchant_set_keys'] ?? [];
        $index = (int) ($progress['craft_enchant_set_enchant_index'] ?? 0);

        if ($index >= count($queue)) {
            $progress['craft_enchant_set_phase'] = 'finalizing';
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => []];
        }

        $key = $keys[$index] ?? (string) $index;
        $craftedItemId = $progress['craft_enchant_set_crafted_item_ids'][$key] ?? null;

        if (is_null($craftedItemId)) {
            $progress['craft_enchant_set_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => []];
        }

        $craftedItem = Item::find($craftedItemId);

        if (is_null($craftedItem)) {
            $progress['craft_enchant_set_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'failed',
                'failure' => 'Crafted item was removed before enchanting.',
            ]]];
        }

        $beforeSnapshot = $this->itemDetails($craftedItem, null, false);
        $plan = $progress['enchant_plan'][$key] ?? [];
        $prefixId = $plan['prefix_affix_id'] ?? null;
        $suffixId = $plan['suffix_affix_id'] ?? null;
        $prefixAffix = is_null($prefixId) ? null : ItemAffix::find($prefixId);
        $suffixAffix = is_null($suffixId) ? null : ItemAffix::find($suffixId);
        $affixIds = array_values(array_filter([$prefixId, $suffixId], fn ($affixId) => ! is_null($affixId)));

        if (empty($affixIds)) {
            $progress['craft_enchant_set_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'failed',
                'crafted_item' => $beforeSnapshot,
                'failure' => 'This item has no prefix or suffix selected to enchant.',
            ]]];
        }

        if ($this->intBlockedForEnchant($character, $affixIds)) {
            return ['end_reason' => BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING, 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'stopped',
                'crafted_item' => $beforeSnapshot,
                'prefix_affix_name' => $prefixAffix?->name,
                'suffix_affix_name' => $suffixAffix?->name,
                'prefix_affix' => $prefixAffix?->toArray(),
                'suffix_affix' => $suffixAffix?->toArray(),
                'failure' => 'Your Intelligence is too low for the selected enchantment.',
            ]]];
        }

        $goldBeforeEnchant = $character->gold;
        $enchantAttempt = $this->tryEnchantItem($character->refresh(), $craftedItem, $affixIds);
        $goldSpent = max(0, $goldBeforeEnchant - $character->refresh()->gold);

        $progress = $batchCrafting->fresh()->progress ?? [];

        if (! $enchantAttempt['attempted']) {
            $progress['craft_enchant_set_current_prefix'] = $prefixAffix?->name;
            $progress['craft_enchant_set_current_suffix'] = $suffixAffix?->name;
            $progress['craft_enchant_set_current_prefix_affix'] = $prefixAffix?->toArray();
            $progress['craft_enchant_set_current_suffix_affix'] = $suffixAffix?->toArray();
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'failed',
                'crafted_item' => $beforeSnapshot,
                'prefix_affix_name' => $prefixAffix?->name,
                'suffix_affix_name' => $suffixAffix?->name,
                'prefix_affix' => $prefixAffix?->toArray(),
                'suffix_affix' => $suffixAffix?->toArray(),
                'prefix_applied' => false,
                'suffix_applied' => false,
                'failure' => 'Enchanting service did not apply an enchantment.',
            ]]];
        }

        $afterItem = $enchantAttempt['item'];

        if (is_null($afterItem)) {
            $progress['craft_enchant_set_enchant_index'] = $index + 1;
            $progress['craft_enchant_set_completed_work_units'] = ((int) ($progress['craft_enchant_set_completed_work_units'] ?? 0)) + 2;
            $progress['craft_enchant_set_crafted_item_ids'][$key] = null;
            $progress['craft_enchant_set_current_prefix'] = $prefixAffix?->name;
            $progress['craft_enchant_set_current_suffix'] = $suffixAffix?->name;
            $progress['craft_enchant_set_current_prefix_affix'] = $prefixAffix?->toArray();
            $progress['craft_enchant_set_current_suffix_affix'] = $suffixAffix?->toArray();
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['destroyed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'destroyed',
                'destroyed_item' => $this->removedItemDetails($beforeSnapshot, 'destroyed'),
                'prefix_affix_name' => $prefixAffix?->name,
                'suffix_affix_name' => $suffixAffix?->name,
                'prefix_affix' => $prefixAffix?->toArray(),
                'suffix_affix' => $suffixAffix?->toArray(),
                'prefix_applied' => false,
                'suffix_applied' => false,
                'gold_spent' => $goldSpent,
                'failure' => 'The item shattered while enchanting and was destroyed.',
            ]]];
        }

        $prefixApplied = ! is_null($prefixId) && $afterItem->item_prefix_id === $prefixId;
        $suffixApplied = ! is_null($suffixId) && $afterItem->item_suffix_id === $suffixId;
        $enchantedSnapshot = $this->itemDetails($afterItem, null, false);

        if ($prefixApplied) {
            $progress['craft_enchant_set_prefix_applied_count'] = ((int) ($progress['craft_enchant_set_prefix_applied_count'] ?? 0)) + 1;
        }

        if ($suffixApplied) {
            $progress['craft_enchant_set_suffix_applied_count'] = ((int) ($progress['craft_enchant_set_suffix_applied_count'] ?? 0)) + 1;
        }

        $progress['craft_enchant_set_crafted_item_ids'][$key] = $afterItem->id;
        $progress['craft_enchant_set_current_item'] = $enchantedSnapshot;
        $progress['craft_enchant_set_current_prefix'] = $prefixAffix?->name;
        $progress['craft_enchant_set_current_suffix'] = $suffixAffix?->name;
        $progress['craft_enchant_set_current_prefix_affix'] = $prefixAffix?->toArray();
        $progress['craft_enchant_set_current_suffix_affix'] = $suffixAffix?->toArray();

        if (! $prefixApplied && ! $suffixApplied) {
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'failed',
                'crafted_item' => $beforeSnapshot,
                'prefix_affix_name' => $prefixAffix?->name,
                'suffix_affix_name' => $suffixAffix?->name,
                'prefix_affix' => $prefixAffix?->toArray(),
                'suffix_affix' => $suffixAffix?->toArray(),
                'prefix_applied' => false,
                'suffix_applied' => false,
                'gold_spent' => $goldSpent,
                'failure' => 'Enchanting service did not apply either enchantment.',
            ]]];
        }

        $progress['craft_enchant_set_enchant_index'] = $index + 1;
        $progress['craft_enchant_set_completed_work_units'] = ((int) ($progress['craft_enchant_set_completed_work_units'] ?? 0)) + 2;
        $batchCrafting->update(['progress' => $progress]);

        return ['counts' => ['enchanted_count' => 1], 'actions' => [[
            'action' => 'craft_enchant_set_enchant',
            'phase' => 'enchanting',
            'status' => $prefixApplied && $suffixApplied ? 'double_enchanted' : 'enchanted',
            'crafted_item' => $beforeSnapshot,
            'enchanted_item' => $enchantedSnapshot,
            'prefix_affix_name' => $prefixAffix?->name,
            'suffix_affix_name' => $suffixAffix?->name,
            'prefix_affix' => $prefixAffix?->toArray(),
            'suffix_affix' => $suffixAffix?->toArray(),
            'prefix_applied' => $prefixApplied,
            'suffix_applied' => $suffixApplied,
            'gold_spent' => $goldSpent,
        ]]];
    }

    private function craftEnchantSetFinalizePhaseSingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $queue = $progress['craft_enchant_set_queue'] ?? [];
        $keys = $progress['craft_enchant_set_keys'] ?? [];
        $index = (int) ($progress['craft_enchant_set_finalize_index'] ?? 0);

        if ($index >= count($queue)) {
            return ['end_reason' => BatchCraftingEndReason::CRAFT_ENCHANT_SET_COMPLETE];
        }

        $key = $keys[$index] ?? (string) $index;
        $progress['craft_enchant_set_finalize_index'] = $index + 1;
        $batchCrafting->update(['progress' => $progress]);

        $itemId = $progress['craft_enchant_set_crafted_item_ids'][$key] ?? null;

        if (is_null($itemId)) {
            return ['counts' => []];
        }

        $item = Item::find($itemId);

        if (is_null($item)) {
            return ['counts' => []];
        }

        $finalSnapshot = $this->itemDetails($item, null, false);
        $dispositionResult = $this->applyDispositionForItem($batchCrafting, $character->refresh(), $item, $disposition);

        $progress = $batchCrafting->fresh()->progress ?? [];
        $progress['craft_enchant_set_completed_final_count'] = ((int) ($progress['craft_enchant_set_completed_final_count'] ?? 0)) + 1;
        $batchCrafting->update(['progress' => $progress]);

        $result = [
            'counts' => $dispositionResult['counts'],
            'actions' => [array_merge([
                'action' => 'craft_enchant_set_finalize',
                'phase' => 'finalizing',
                'crafted_item' => $finalSnapshot,
            ], $dispositionResult['details'])],
        ];

        if ($this->shouldMoveKeptOutputToBatchSet($disposition)) {
            $this->commitKeptItemToBatchSet($batchCrafting, $character->refresh(), $result);

            if (isset($result['end_reason']) && $result['end_reason'] === BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL) {
                return $result;
            }

            foreach ($result['actions'] as $actionIndex => $action) {
                if (isset($action['kept_item']['set_slot_id'])) {
                    $result['actions'][$actionIndex]['destination_set'] = InventorySet::BATCH_CRAFTING_SET_NAME;
                    $result['actions'][$actionIndex]['created_in_crafted_items_set'] = true;
                }
            }
        }

        return $result;
    }

    private function processEventCraft(BatchCrafting $batchCrafting, Character $character): array
    {
        $goal = $this->globalEventGoalEligibilityService->currentCraftingGoalFor($character);

        if (is_null($goal)) {
            return ['end_reason' => $this->eventCraftUnavailableReason($batchCrafting, $character)];
        }

        return $this->processRepeatedActions(BatchCraftingService::EVENT_ITEMS_PER_SET_TICK, function () use ($batchCrafting, $character) {
            return $this->processEventCraftSingle($batchCrafting->refresh(), $character->refresh());
        });
    }

    private function processEventCraftSingle(BatchCrafting $batchCrafting, Character $character): array
    {
        $goal = $this->globalEventGoalEligibilityService->currentCraftingGoalFor($character);

        if (is_null($goal)) {
            return ['end_reason' => $this->eventCraftUnavailableReason($batchCrafting, $character)];
        }

        $progress = $batchCrafting->progress ?? [];
        $queue = $progress['event_craft_queue'] ?? [];
        $index = (int) ($progress['event_craft_index'] ?? 0);

        if (empty($queue)) {
            return ['end_reason' => BatchCraftingEndReason::EVENT_NO_CRAFTABLE_ITEMS];
        }

        if ($index >= count($queue)) {
            $index = 0;
        }

        $target = $queue[$index];
        $progress['event_craft_index'] = $index + 1;
        $batchCrafting->update(['progress' => $progress]);
        $item = $this->eventCraftableItem($character, $target);

        if (is_null($item)) {
            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'event_craft',
                'status' => 'skipped',
                'failure' => 'No event craftable item found for type: '.$target['type'],
            ]]];
        }

        $skillBefore = $this->craftingSkillSnapshot($character, $target['crafting_type']);
        $crafted = $this->craftingService->craftForBatch($character->refresh(), $item, $target['crafting_type']);
        $craftingXpGained = $this->skillExperienceGained($skillBefore, $this->craftingSkillSnapshot($character->refresh(), $target['crafting_type']));

        if (! $crafted['success'] || is_null($crafted['item'])) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'event_craft',
                'failure' => 'Crafting attempt failed. No event item was produced.',
            ]]];
        }

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character->refresh(), $crafted['item']);

        return ['counts' => ['crafted_count' => 1], 'actions' => [[
            'action' => 'event_craft',
            'crafted_item' => $this->itemDetails($item),
            'event_goal_id' => $goal->id,
            'crafting_xp_gained' => $craftingXpGained,
        ]]];
    }

    private function processEventEnchant(BatchCrafting $batchCrafting, Character $character): array
    {
        $goal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);

        if (is_null($goal)) {
            return ['end_reason' => $this->eventEnchantUnavailableReason($batchCrafting, $character)];
        }

        $progress = $batchCrafting->progress ?? [];
        $eventSlot = $this->nextEventInventorySlot($character, $goal);

        if (! is_null($eventSlot) && ($progress['event_enchant_phase'] ?? 'enchant_event_inventory') === 'enchant_event_inventory') {
            return $this->processUntilDepletedOrEndReason(BatchCraftingService::EVENT_ITEMS_PER_SET_TICK, function () use ($batchCrafting, $character) {
                return $this->processEventEnchantSingle($batchCrafting->refresh(), $character->refresh());
            });
        }

        if (($progress['event_enchant_phase'] ?? 'enchant_event_inventory') !== 'enchant_fallback_set') {
            $craftResult = $this->processEventFallbackCraftSet($batchCrafting, $character);

            if (isset($craftResult['end_reason'])) {
                return $craftResult;
            }

            $enchantResult = $this->processEventFallbackEnchantSet($batchCrafting->refresh(), $character->refresh());

            return $this->mergeTickResults($craftResult, $enchantResult);
        }

        return $this->processEventFallbackEnchantSet($batchCrafting, $character);
    }

    private function processEventEnchantSingle(BatchCrafting $batchCrafting, Character $character): array
    {
        $goal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);

        if (is_null($goal)) {
            return ['end_reason' => $this->eventEnchantUnavailableReason($batchCrafting, $character)];
        }

        $slot = $this->nextEventInventorySlot($character, $goal);

        if (is_null($slot)) {
            return ['depleted' => true];
        }

        $affixIds = $this->eventBatchEnchantingAffixSelector->affixIdsForEventEnchant($character, $slot->item);

        if (empty($affixIds)) {
            return ['end_reason' => BatchCraftingEndReason::EVENT_NO_AFFIXES];
        }

        if ($this->intBlockedForEnchant($character, $affixIds)) {
            return ['end_reason' => BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING, 'actions' => [[
                'action' => 'event_enchant',
                'status' => 'stopped',
                'crafted_item' => $this->itemDetails($slot->item, $slot->id),
                'failure' => 'Your Intelligence is too low for the selected enchantment.',
            ]]];
        }

        $cost = $this->enchantingService->getCostOfEnchantment($character, $affixIds, $slot->item_id);

        if ($character->gold < $cost) {
            return ['end_reason' => BatchCraftingEndReason::NO_CURRENCY];
        }

        $skillBefore = $this->skillSnapshot($character, SkillTypeValue::ENCHANTING->value, true);
        $this->enchantingService->enchant($character, [
            'affix_ids' => $affixIds,
            'enchant_for_event' => true,
        ], $slot, $cost);
        $enchantingXpGained = $this->skillExperienceGained($skillBefore, $this->skillSnapshot($character->refresh(), SkillTypeValue::ENCHANTING->value, true));
        $enchantingXpGained = $enchantingXpGained > 0 ? $enchantingXpGained : $this->acceptedEventEnchantExperience($character->refresh(), $slot);

        return ['counts' => ['enchanted_count' => 1], 'actions' => [[
            'action' => 'event_enchant',
            'enchanted_item' => $this->itemDetails($slot->item, $slot->id),
            'event_goal_id' => $goal->id,
            'affix_count' => count($affixIds),
            'enchanting_xp_gained' => $enchantingXpGained,
        ]]];
    }

    private function processEventFallbackCraftSet(BatchCrafting $batchCrafting, Character $character): array
    {
        if (! $this->batchCraftingSetService->canAccept($character, BatchCraftingService::EVENT_ITEMS_PER_SET_TICK)) {
            return ['end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL];
        }

        $progress = $batchCrafting->progress ?? [];
        $progress['event_enchant_phase'] = 'craft_fallback_set';
        $progress['event_fallback_phase'] = 'craft_fallback_set';
        $progress['event_fallback_event_slot_ids'] = [];
        $batchCrafting->update(['progress' => $progress]);

        $result = $this->processRepeatedActions(BatchCraftingService::EVENT_ITEMS_PER_SET_TICK, function () use ($batchCrafting, $character) {
            return $this->processEventFallbackCraftSingle($batchCrafting->refresh(), $character->refresh());
        });

        $progress = $batchCrafting->refresh()->progress ?? [];
        $progress['event_enchant_phase'] = 'enchant_fallback_set';
        $progress['event_fallback_phase'] = 'enchant_fallback_set';
        $progress['event_fallback_crafted_this_tick'] = $result['counts']['crafted_count'] ?? 0;
        $batchCrafting->update(['progress' => $progress]);

        return $result;
    }

    private function processEventFallbackCraftSingle(BatchCrafting $batchCrafting, Character $character): array
    {
        $progress = $batchCrafting->progress ?? [];
        $queue = $progress['event_craft_queue'] ?? [
            ['type' => 'weapon', 'crafting_type' => 'weapon'],
            ['type' => 'armour', 'crafting_type' => 'armour'],
            ['type' => 'ring', 'crafting_type' => 'ring'],
            ['type' => 'spell_damage', 'crafting_type' => 'spell'],
            ['type' => 'spell_healing', 'crafting_type' => 'spell'],
        ];
        $index = (int) ($progress['event_craft_index'] ?? 0);

        if ($index >= count($queue)) {
            $index = 0;
        }

        $target = $queue[$index];
        $item = $this->eventCraftableItem($character, $target);
        $progress['event_craft_index'] = $index + 1;
        $batchCrafting->update(['progress' => $progress]);

        if (is_null($item)) {
            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'event_fallback_craft',
                'status' => 'skipped',
                'failure' => 'No fallback craftable item found for type: '.$target['type'],
            ]]];
        }

        $skillBefore = $this->craftingSkillSnapshot($character, $target['crafting_type']);
        $crafted = $this->craftingService->craftForBatch($character->refresh(), $item, $target['crafting_type']);
        $craftingXpGained = $this->skillExperienceGained($skillBefore, $this->craftingSkillSnapshot($character->refresh(), $target['crafting_type']));

        if (! $crafted['success'] || is_null($crafted['item'])) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'event_fallback_craft',
                'failure' => 'Crafting attempt failed. No item was produced.',
            ]]];
        }

        $eventSlot = $this->createEventFallbackSlot($character->refresh(), $crafted['item']);
        $progress = $batchCrafting->refresh()->progress ?? [];
        $progress['event_fallback_event_slot_ids'] = array_values(array_filter([
            ...($progress['event_fallback_event_slot_ids'] ?? []),
            $eventSlot->id,
        ]));
        $batchCrafting->update(['progress' => $progress]);

        return ['counts' => ['crafted_count' => 1], 'actions' => [[
            'action' => 'event_fallback_craft',
            'crafted_item' => $this->itemDetails($crafted['item'], $eventSlot->id),
            'crafting_xp_gained' => $craftingXpGained,
        ]]];
    }

    private function createEventFallbackSlot(Character $character, Item $item): GlobalEventCraftingInventorySlot
    {
        $goal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);
        $inventory = GlobalEventCraftingInventory::firstOrCreate([
            'global_event_goal_id' => $goal?->id,
            'character_id' => $character->id,
        ]);

        return GlobalEventCraftingInventorySlot::create([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => $item->id,
        ]);
    }

    private function processEventFallbackEnchantSet(BatchCrafting $batchCrafting, Character $character): array
    {
        $result = $this->processUntilDepletedOrEndReason(BatchCraftingService::EVENT_ITEMS_PER_SET_TICK, function () use ($batchCrafting, $character) {
            return $this->processEventFallbackEnchantSingle($batchCrafting->refresh(), $character->refresh());
        });

        $progress = $batchCrafting->refresh()->progress ?? [];
        $progress['event_enchant_phase'] = 'craft_fallback_set';
        $progress['event_fallback_phase'] = 'craft_fallback_set';
        $progress['event_fallback_enchanted_this_tick'] = $result['counts']['enchanted_count'] ?? 0;
        $batchCrafting->update(['progress' => $progress]);

        return $result;
    }

    private function processEventFallbackEnchantSingle(BatchCrafting $batchCrafting, Character $character): array
    {
        $goal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);

        if (is_null($goal)) {
            return ['end_reason' => $this->eventEnchantUnavailableReason($batchCrafting, $character)];
        }

        $progress = $batchCrafting->progress ?? [];
        $slotIds = $progress['event_fallback_event_slot_ids'] ?? [];
        $slotId = array_shift($slotIds);

        if (is_null($slotId)) {
            return ['depleted' => true];
        }

        $progress['event_fallback_event_slot_ids'] = $slotIds;
        $batchCrafting->update(['progress' => $progress]);
        $slot = GlobalEventCraftingInventorySlot::find($slotId);

        if (is_null($slot)) {
            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'event_fallback_enchant',
                'status' => 'skipped',
                'failure' => 'Fallback crafted event item was removed before enchanting.',
            ]]];
        }

        $affixIds = $this->eventBatchEnchantingAffixSelector->affixIdsForEventEnchant($character, $slot->item);

        if (empty($affixIds)) {
            return ['end_reason' => BatchCraftingEndReason::EVENT_NO_AFFIXES];
        }

        if ($this->intBlockedForEnchant($character, $affixIds)) {
            return ['end_reason' => BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING, 'actions' => [[
                'action' => 'event_fallback_enchant',
                'status' => 'stopped',
                'crafted_item' => $this->itemDetails($slot->item, $slot->id),
                'failure' => 'Your Intelligence is too low for the selected enchantment.',
            ]]];
        }

        $cost = $this->enchantingService->getCostOfEnchantment($character, $affixIds, $slot->item_id);

        if ($character->gold < $cost) {
            return ['end_reason' => BatchCraftingEndReason::NO_CURRENCY];
        }

        $skillBefore = $this->skillSnapshot($character, SkillTypeValue::ENCHANTING->value, true);
        $this->enchantingService->enchant($character, [
            'affix_ids' => $affixIds,
            'enchant_for_event' => true,
        ], $slot, $cost);
        $enchantingXpGained = $this->skillExperienceGained($skillBefore, $this->skillSnapshot($character->refresh(), SkillTypeValue::ENCHANTING->value, true));
        $enchantingXpGained = $enchantingXpGained > 0 ? $enchantingXpGained : $this->acceptedEventEnchantExperience($character->refresh(), $slot);

        return ['counts' => ['enchanted_count' => 1], 'actions' => [[
            'action' => 'event_fallback_enchant',
            'enchanted_item' => $this->itemDetails($slot->item, $slot->id),
            'event_goal_id' => $goal->id,
            'affix_count' => count($affixIds),
            'enchanting_xp_gained' => $enchantingXpGained,
        ]]];
    }

    private function processCraftSpecificItem(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $craftAmount = (int) ($progress['craft_amount'] ?? 0);
        $craftedSoFar = (int) ($progress['craft_specific_count'] ?? 0);

        if ($craftAmount > 0 && $craftedSoFar >= $craftAmount) {
            return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED];
        }

        if ($craftAmount > 0) {
            $result = $this->processRepeatedActions($craftAmount - $craftedSoFar, function () use ($batchCrafting, $character, $disposition, $progress) {
                return $this->processCraftSpecificItemSingle($batchCrafting->refresh(), $character->refresh(), $disposition, $batchCrafting->refresh()->progress ?? $progress);
            });

            if (((int) (($batchCrafting->refresh()->progress ?? [])['craft_specific_count'] ?? 0)) >= $craftAmount && ! isset($result['end_reason'])) {
                $result['end_reason'] = BatchCraftingEndReason::AMOUNT_REACHED;
            }

            return $result;
        }

        return $this->processCraftSpecificItemSingle($batchCrafting, $character, $disposition, $progress);
    }

    private function processCraftSpecificItemSingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $craftAmount = (int) ($progress['craft_amount'] ?? 0);
        $craftedSoFar = (int) ($progress['craft_specific_count'] ?? 0);

        if ($craftAmount > 0 && $craftedSoFar >= $craftAmount) {
            return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED];
        }

        if ($this->shouldMoveKeptOutputToBatchSet($disposition) && ! $this->batchCraftingSetService->canAccept($character, 1)) {
            return ['end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL];
        }

        $craftingType = $progress['specific_crafting_type'] ?? null;
        $itemId = (int) ($progress['specific_item_id'] ?? 0);

        if (is_null($craftingType) || $itemId <= 0) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $craftableItems = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => $craftingType,
        ], false);

        $craftableItem = $craftableItems->first(fn ($item) => (int) $item->id === $itemId);
        $item = is_null($craftableItem) ? null : Item::find($craftableItem->id);

        if (is_null($item)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $result = $this->craftItem($batchCrafting, $character, $disposition, $item, $craftingType, $this->shouldMoveKeptOutputToBatchSet($disposition));

        if (isset($result['counts']['crafted_count']) && $result['counts']['crafted_count'] > 0) {
            $progress['craft_specific_count'] = $craftedSoFar + 1;
            $batchCrafting->update(['progress' => $progress]);
        }

        if ($this->shouldMoveKeptOutputToBatchSet($disposition)) {
            $this->commitKeptItemToBatchSet($batchCrafting, $character->refresh(), $result);
        }

        return $result;
    }

    private function craftItem(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, Item $item, string $craftingType, bool $suppressSuccessServerMessage = false): array
    {
        $craftResult = $this->craftingService->craftForBatch($character->refresh(), $item, $craftingType, $suppressSuccessServerMessage);

        if (! $craftResult['success'] || is_null($craftResult['item'])) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft',
                'failure' => 'Crafting attempt failed. No item was produced.',
            ]]];
        }

        $craftedItem = $craftResult['item'];
        $action = ['action' => 'craft', 'crafted_item' => $this->itemDetails($craftedItem)];
        $counts = ['crafted_count' => 1];
        $dispositionResult = $this->applyDispositionForItem($batchCrafting, $character->refresh(), $craftedItem, $disposition);
        $counts = $this->mergeCounts($counts, $dispositionResult['counts']);
        $action = array_merge($action, $dispositionResult['details']);

        $result = ['counts' => $counts, 'actions' => [$action]];

        if (isset($dispositionResult['end_reason'])) {
            $result['end_reason'] = $dispositionResult['end_reason'];
        }

        return $result;
    }

    private function craftExperienceTargets(?string $selectedSkill): array
    {
        return match ($selectedSkill) {
            'weapon' => array_map(fn (string $weaponType) => ['type' => $weaponType, 'crafting_type' => $weaponType], ItemType::validWeapons()),
            'armour' => array_map(fn (string $armourType) => ['type' => $armourType, 'crafting_type' => 'armour'], ArmourType::allTypes()),
            'ring' => [
                ['type' => ItemType::RING->value, 'crafting_type' => 'ring'],
            ],
            'spell' => [
                ['type' => ItemType::SPELL_DAMAGE->value, 'crafting_type' => 'spell'],
                ['type' => ItemType::SPELL_HEALING->value, 'crafting_type' => 'spell'],
            ],
            default => array_merge(
                array_map(fn (string $weaponType) => ['type' => $weaponType, 'crafting_type' => $weaponType], ItemType::validWeapons()),
                array_map(fn (string $armourType) => ['type' => $armourType, 'crafting_type' => 'armour'], ArmourType::allTypes()),
                [
                    ['type' => ItemType::RING->value, 'crafting_type' => 'ring'],
                    ['type' => ItemType::RING->value, 'crafting_type' => 'ring'],
                    ['type' => ItemType::SPELL_DAMAGE->value, 'crafting_type' => 'spell'],
                    ['type' => ItemType::SPELL_HEALING->value, 'crafting_type' => 'spell'],
                ],
            ),
        };
    }

    private function allCraftingSkillsMaxed(Character $character): bool
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

    private function isEnchantingSkillMaxed(Character $character): bool
    {
        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->with('baseSkill')
            ->first();

        return ! is_null($skill) && $skill->level >= $skill->max_level;
    }

    private function nonMaxedCraftExperienceTargets(Character $character): array
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

        $maxedSkillNames = $craftingSkills
            ->filter(fn ($skill) => $skill->level >= $skill->max_level)
            ->map(fn ($skill) => $skill->baseSkill->name ?? $skill->name)
            ->all();

        $targets = [];

        if (! in_array('Weapon Crafting', $maxedSkillNames, true)) {
            foreach (ItemType::validWeapons() as $weaponType) {
                $targets[] = ['type' => $weaponType, 'crafting_type' => $weaponType];
            }
        }

        if (! in_array('Armour Crafting', $maxedSkillNames, true)) {
            foreach (ArmourType::allTypes() as $armourType) {
                $targets[] = ['type' => $armourType, 'crafting_type' => 'armour'];
            }
        }

        if (! in_array('Ring Crafting', $maxedSkillNames, true)) {
            $targets[] = ['type' => ItemType::RING->value, 'crafting_type' => 'ring'];
            $targets[] = ['type' => ItemType::RING->value, 'crafting_type' => 'ring'];
        }

        if (! in_array('Spell Crafting', $maxedSkillNames, true)) {
            $targets[] = ['type' => ItemType::SPELL_DAMAGE->value, 'crafting_type' => 'spell'];
            $targets[] = ['type' => ItemType::SPELL_HEALING->value, 'crafting_type' => 'spell'];
        }

        return $targets;
    }

    private function specificCraftableItems(Character $character, string $craftingType)
    {
        return $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => $craftingType,
        ], false);
    }

    private function eventCraftableItem(Character $character, array $target): ?Item
    {
        if ($target['type'] === 'weapon') {
            foreach (ItemType::validWeapons() as $weaponType) {
                $item = $this->craftableItemForTarget($character, $weaponType, $weaponType);

                if (! is_null($item)) {
                    return $item;
                }
            }

            return null;
        }

        if ($target['type'] === 'armour') {
            foreach (ArmourType::allTypes() as $armourType) {
                $item = $this->craftableItemForTarget($character, $armourType, 'armour');

                if (! is_null($item)) {
                    return $item;
                }
            }

            return null;
        }

        return $this->craftableItemForTarget($character, $target['type'], $target['crafting_type']);
    }

    private function craftableItemForTarget(Character $character, string $type, string $craftingType): ?Item
    {
        try {
            $craftableItem = $this->specificCraftableItems($character, $craftingType)
                ->filter(fn ($item) => $item->type === $type)
                ->sortBy([
                    ['cost', 'asc'],
                    ['skill_level_required', 'asc'],
                    ['id', 'asc'],
                ])
                ->first();
        } catch (\Throwable) {
            return null;
        }

        return is_null($craftableItem) ? null : Item::find($craftableItem->id);
    }

    private function xpEligibleCraftableItem(Character $character, string $craftingType, string $type): ?Item
    {
        try {
            $candidates = $this->specificCraftableItems($character, $craftingType)
                ->filter(fn ($craftableItem) => $craftableItem->type === $type);
        } catch (\Throwable) {
            return null;
        }

        $skillLevel = $this->craftingSkillLevelForType($character, $craftingType);

        return $this->xpEligibleItemFromCandidates($candidates, $skillLevel);
    }

    private function craftingSkillLevelForType(Character $character, string $craftingType): ?int
    {
        $skillName = in_array($craftingType, ItemType::validWeapons(), true)
            ? 'Weapon Crafting'
            : ucfirst($craftingType) . ' Crafting';

        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('name', $skillName))
            ->first();

        return $skill?->level;
    }

    private function xpEligibleItemFromCandidates(Collection $candidates, ?int $skillLevel): ?Item
    {
        if (is_null($skillLevel) || $candidates->isEmpty()) {
            return null;
        }

        return Item::whereIn('id', $candidates->pluck('id'))
            ->where('skill_level_trivial', '>=', $skillLevel)
            ->orderByDesc('skill_level_required')
            ->first();
    }

    private function highestCraftableItemForTarget(Character $character, string $type, string $craftingType): ?Item
    {
        try {
            $candidateIds = $this->specificCraftableItems($character, $craftingType)
                ->filter(fn ($item) => $item->type === $type)
                ->pluck('id');
        } catch (\Throwable) {
            return null;
        }

        if ($candidateIds->isEmpty()) {
            return null;
        }

        return Item::whereIn('id', $candidateIds)
            ->orderByDesc('skill_level_required')
            ->orderByDesc('cost')
            ->orderBy('id')
            ->first();
    }

    private function mergeTickResults(array $firstResult, array $secondResult): array
    {
        $result = [
            'counts' => $this->mergeCounts($firstResult['counts'] ?? [], $secondResult['counts'] ?? []),
            'actions' => array_merge($firstResult['actions'] ?? [], $secondResult['actions'] ?? []),
        ];

        if (isset($secondResult['end_reason'])) {
            $result['end_reason'] = $secondResult['end_reason'];
        }

        if (isset($firstResult['end_reason'])) {
            $result['end_reason'] = $firstResult['end_reason'];
        }

        return $result;
    }

    private function nextEventInventorySlot(Character $character, GlobalEventGoal $goal): ?GlobalEventCraftingInventorySlot
    {
        $inventory = GlobalEventCraftingInventory::where('global_event_goal_id', $goal->id)
            ->where('character_id', $character->id)
            ->first();

        if (is_null($inventory)) {
            return null;
        }

        return $inventory->craftingSlots()->with('item')->orderBy('id')->first();
    }

    private function eventCraftUnavailableReason(BatchCrafting $batchCrafting, Character $character): BatchCraftingEndReason
    {
        $progress = $batchCrafting->progress ?? [];
        $goal = GlobalEventGoal::find($progress['event_goal_id'] ?? null);

        if (! is_null($goal) && ! is_null($goal->max_crafts) && $goal->total_crafts >= $goal->max_crafts) {
            return BatchCraftingEndReason::EVENT_GOAL_COMPLETE;
        }

        $event = Event::find($progress['event_id'] ?? null);

        if (is_null($event) || ! $this->globalEventGoalEligibilityService->isEventRunning($event)) {
            return BatchCraftingEndReason::EVENT_NOT_RUNNING;
        }

        if ($event->current_event_goal_step !== GlobalEventSteps::CRAFT) {
            return BatchCraftingEndReason::EVENT_STEP_CHANGED;
        }

        if (! $this->globalEventGoalEligibilityService->isOnEventMap($character, $event)) {
            return BatchCraftingEndReason::EVENT_WRONG_MAP;
        }

        return BatchCraftingEndReason::EVENT_NO_CRAFTABLE_ITEMS;
    }

    private function eventEnchantUnavailableReason(BatchCrafting $batchCrafting, Character $character): BatchCraftingEndReason
    {
        $progress = $batchCrafting->progress ?? [];
        $goal = GlobalEventGoal::find($progress['event_goal_id'] ?? null);

        if (! is_null($goal) && ! is_null($goal->max_enchants) && $goal->total_enchants >= $goal->max_enchants) {
            return BatchCraftingEndReason::EVENT_GOAL_COMPLETE;
        }

        $event = Event::find($progress['event_id'] ?? null);

        if (is_null($event) || ! $this->globalEventGoalEligibilityService->isEventRunning($event)) {
            return BatchCraftingEndReason::EVENT_NOT_RUNNING;
        }

        if ($event->current_event_goal_step !== GlobalEventSteps::ENCHANT) {
            return BatchCraftingEndReason::EVENT_STEP_CHANGED;
        }

        if (! $this->globalEventGoalEligibilityService->isOnEventMap($character, $event)) {
            return BatchCraftingEndReason::EVENT_WRONG_MAP;
        }

        return BatchCraftingEndReason::EVENT_NO_EVENT_ITEMS_TO_ENCHANT;
    }

    private function processRepeatedActions(int $attempts, callable $callback): array
    {
        $counts = [];
        $actions = [];

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $result = $callback();

            if (isset($result['end_reason'])) {
                $counts = $this->mergeCounts($counts, $result['counts'] ?? []);
                $actions = array_merge($actions, $result['actions'] ?? []);

                if (! empty($actions)) {
                    return [
                        'end_reason' => $result['end_reason'],
                        'counts' => $counts,
                        'actions' => $actions,
                    ];
                }

                return $result;
            }

            $counts = $this->mergeCounts($counts, $result['counts'] ?? []);
            $actions = array_merge($actions, $result['actions'] ?? []);
        }

        return ['counts' => $counts, 'actions' => $actions];
    }

    // A callback returning ['depleted' => true] stops the loop without an end_reason, so running out of source items mid-tick ends the tick normally instead of ending the whole batch job.
    private function processUntilDepletedOrEndReason(int $attempts, callable $callback): array
    {
        $counts = [];
        $actions = [];

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $result = $callback();

            if (($result['depleted'] ?? false) === true) {
                break;
            }

            if (isset($result['end_reason'])) {
                return [
                    'end_reason' => $result['end_reason'],
                    'counts' => $this->mergeCounts($counts, $result['counts'] ?? []),
                    'actions' => array_merge($actions, $result['actions'] ?? []),
                ];
            }

            $counts = $this->mergeCounts($counts, $result['counts'] ?? []);
            $actions = array_merge($actions, $result['actions'] ?? []);
        }

        return ['counts' => $counts, 'actions' => $actions];
    }

    private function shouldMoveKeptOutputToBatchSet(BatchCraftingDisposition $disposition): bool
    {
        return in_array($disposition, [
            BatchCraftingDisposition::KEEP,
            BatchCraftingDisposition::KEEP_HIGHEST,
            BatchCraftingDisposition::KEEP_BEST_SELL_REST,
            BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST,
            BatchCraftingDisposition::KEEP_BEST_DESTROY_REST,
        ], true);
    }

    private function retainedCraftedItemsSetSlotsForExperience(BatchCraftingDisposition $disposition): int
    {
        if ($disposition === BatchCraftingDisposition::KEEP) {
            return BatchCraftingService::ITEMS_PER_FULL_SET;
        }

        return count(collect($this->craftSetQueue())->unique('type'));
    }

    private function commitKeptItemToBatchSet(BatchCrafting $batchCrafting, Character $character, array &$result): void
    {
        foreach ($result['actions'] ?? [] as $actionIndex => $action) {
            $itemId = $action['kept_item']['item_id'] ?? null;

            if (is_null($itemId)) {
                continue;
            }

            if (! is_null($action['kept_item']['set_slot_id'] ?? null)) {
                continue;
            }

            $item = Item::find($itemId);

            if (is_null($item)) {
                continue;
            }

            $createResult = $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $item);

            if ($createResult['success']) {
                $result['actions'][$actionIndex]['kept_item']['set_slot_id'] = $createResult['set_slot']->id;
                $this->rememberCommittedKeepBestSlot($batchCrafting, $item, $createResult['set_slot']->id);

                $itemName = $item->affix_name ?? $item->name;
                $enchantAffixName = $action['enchant_affix_name'] ?? null;
                $manualStyleMessage = empty($enchantAffixName)
                    ? 'You crafted a: ' . $itemName . '!'
                    : 'Applied enchantment: ' . $enchantAffixName . ' to: ' . $itemName;

                $this->serverMessageHandler->sendBasicMessageWithLink(
                    $character->user,
                    $manualStyleMessage,
                    $createResult['set_slot']->id,
                    'crafted_items_set',
                    $itemName,
                );

                $this->serverMessageHandler->sendBasicMessageWithLink(
                    $character->user,
                    'Kept: ' . $itemName . ' in your Crafted Items Set.',
                    $createResult['set_slot']->id,
                    'crafted_items_set',
                    $itemName,
                );

                continue;
            }

            if ($createResult['reason'] === 'set_full') {
                $result['end_reason'] = BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL;

                return;
            }

            $result['counts'] = $this->mergeCounts($result['counts'] ?? [], ['failed_count' => 1]);
            $result['actions'][] = [
                'action' => 'keep',
                'status' => 'failed',
                'failure' => 'Could not create item in Crafted Items Set: ' . ($createResult['reason'] ?? 'unknown'),
            ];
        }
    }

    private function rememberCommittedKeepBestSlot(BatchCrafting $batchCrafting, Item $item, int $setSlotId): void
    {
        $disposition = BatchCraftingDisposition::tryFrom($batchCrafting->disposition);

        if (! $this->isKeepBestDisposition($disposition)) {
            return;
        }

        $this->rememberKeepBestReference($batchCrafting, $item, $disposition->value, $setSlotId);
    }

    private function rememberKeepBestReference(BatchCrafting $batchCrafting, Item $item, string $disposition, ?int $setSlotId): void
    {
        $progress = $batchCrafting->fresh()->progress ?? [];
        $itemType = $item->type ?? 'weapon';
        $keepBestItems = $progress['keep_best_item_ids'] ?? [];
        $keepBestReferences = $progress['keep_best_item_refs'] ?? [];

        $keepBestItems[$itemType] = $item->id;
        $keepBestReferences[$itemType] = [
            'item_id' => $item->id,
            'set_slot_id' => $setSlotId,
            'item_type' => $itemType,
            'disposition' => $disposition,
        ];

        $batchCrafting->update([
            'progress' => array_merge($progress, [
                'keep_best_item_ids' => $keepBestItems,
                'keep_best_item_refs' => $keepBestReferences,
            ]),
        ]);
    }

    private function isKeepBestDisposition(?BatchCraftingDisposition $disposition): bool
    {
        return in_array($disposition, [
            BatchCraftingDisposition::KEEP_BEST_SELL_REST,
            BatchCraftingDisposition::KEEP_BEST_DESTROY_REST,
            BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST,
        ], true);
    }

    private function pickEnchantableSlot(Character $character): ?InventorySlot
    {
        if (is_null($character->inventory)) {
            return null;
        }

        return $character->inventory->slots()
            ->where('equipped', false)
            ->whereHas('item', function ($query) {
                $query->whereNotIn('type', ['trinket', 'artifact', 'alchemy', 'gem', 'quest'])
                    ->whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id');
            })
            ->first();
    }

    private function resolveIntendedAffixIdsForEnchant(Character $character, ?array $affixIds = null): array
    {
        $affixIds = array_values(array_filter($affixIds ?? [], fn ($affixId) => ! is_null($affixId)));

        if (! empty($affixIds)) {
            return $affixIds;
        }

        $enchantingSkill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->first();

        if (is_null($enchantingSkill)) {
            return [];
        }

        $affix = ItemAffix::where('skill_level_required', '<=', $enchantingSkill->level)
            ->where('skill_level_trivial', '>=', $enchantingSkill->level)
            ->orderByDesc('skill_level_required')
            ->first();

        if (is_null($affix)) {
            return [];
        }

        return [$affix->id];
    }

    private function intBlockedForEnchant(Character $character, ?array $affixIds = null): bool
    {
        $resolvedAffixIds = $this->resolveIntendedAffixIdsForEnchant($character, $affixIds);

        if (empty($resolvedAffixIds)) {
            return false;
        }

        $characterInt = $character->getInformation()->statMod('int');
        $selectedAffixes = ItemAffix::whereIn('id', $resolvedAffixIds)->get();

        return $selectedAffixes->contains(fn (ItemAffix $affix) => $characterInt < $affix->int_required);
    }

    private function tryEnchantSlot(Character $character, InventorySlot $slot, ?array $affixIds = null): bool
    {
        $resolvedAffixIds = $this->resolveIntendedAffixIdsForEnchant($character, $affixIds);

        if (empty($resolvedAffixIds)) {
            return false;
        }

        if ($this->intBlockedForEnchant($character, $resolvedAffixIds)) {
            return false;
        }

        $cost = $this->enchantingService->getCostOfEnchantment($character, $resolvedAffixIds, $slot->item_id);

        if ($character->gold < $cost) {
            return false;
        }

        $this->enchantingService->enchant($character, [
            'affix_ids' => $resolvedAffixIds,
            'enchant_for_event' => false,
        ], $slot, $cost);

        return true;
    }

    /**
     * Try to enchant a Batch Crafting item directly, with no InventorySlot involved.
     *
     * Mirrors tryEnchantSlot()'s gating (resolve affixes, INT block, affordability)
     * but calls EnchantingService::enchantItemForBatch(). 'attempted' is false when
     * the enchant was never tried; when true, a null 'item' means it was destroyed.
     */
    private function tryEnchantItem(Character $character, Item $item, ?array $affixIds = null, bool $suppressSuccessServerMessage = false): array
    {
        $resolvedAffixIds = $this->resolveIntendedAffixIdsForEnchant($character, $affixIds);

        if (empty($resolvedAffixIds)) {
            return ['attempted' => false, 'item' => null, 'affix_names' => []];
        }

        if ($this->intBlockedForEnchant($character, $resolvedAffixIds)) {
            return ['attempted' => false, 'item' => null, 'affix_names' => []];
        }

        $cost = $this->enchantingService->getCostOfEnchantment($character, $resolvedAffixIds, $item->id);

        if ($character->gold < $cost) {
            return ['attempted' => false, 'item' => null, 'affix_names' => []];
        }

        $affixNames = ItemAffix::whereIn('id', $resolvedAffixIds)->pluck('name')->all();
        $enchantResult = $this->enchantingService->enchantItemForBatch($character, $item, $resolvedAffixIds, $cost, $suppressSuccessServerMessage);

        return ['attempted' => true, 'item' => $enchantResult['success'] ? $enchantResult['item'] : null, 'affix_names' => $affixNames];
    }

    private function applyDispositionForItem(BatchCrafting $batchCrafting, Character $character, Item $item, BatchCraftingDisposition $disposition): array
    {
        return match ($disposition) {
            BatchCraftingDisposition::KEEP => [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $this->itemDetails($item)],
            ],
            BatchCraftingDisposition::KEEP_HIGHEST => $this->applyKeepHighestItem($batchCrafting, $character, $item),
            BatchCraftingDisposition::SELL => $this->sellItem($character, $item),
            BatchCraftingDisposition::DESTROY => $this->destroyItem($character, $item),
            BatchCraftingDisposition::LIST => $this->listItem($batchCrafting, $character, $item),
            BatchCraftingDisposition::DISENCHANT => $this->disenchantItem($character, $item),
            BatchCraftingDisposition::KEEP_BEST_SELL_REST => $this->applyKeepBestAndRestItem($batchCrafting, $character, $item, 'sell'),
            BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST => $this->applyKeepBestAndRestItem($batchCrafting, $character, $item, 'disenchant'),
            BatchCraftingDisposition::KEEP_BEST_DESTROY_REST => $this->applyKeepBestAndRestItem($batchCrafting, $character, $item, 'destroy'),
            BatchCraftingDisposition::USE_NOW => [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $this->itemDetails($item)],
            ],
        };
    }

    /**
     * Loser action naming here (sell/destroy/disenchant) matches the player-facing
     * disposition labels: Keep Best and Sell/Destroy/Disenchant Rest.
     */
    private function applyKeepBestAndRestItem(BatchCrafting $batchCrafting, Character $character, Item $newItem, string $loserAction): array
    {
        $itemType = $newItem->type ?? 'weapon';
        $newLevel = (int) ($newItem->skill_level_required ?? 0);
        $progress = $batchCrafting->progress ?? [];
        $keepBestItems = $progress['keep_best_item_ids'] ?? [];
        $keepBestReferences = $progress['keep_best_item_refs'] ?? [];
        $trackedReference = $keepBestReferences[$itemType] ?? [];
        $trackedItemId = $trackedReference['item_id'] ?? $keepBestItems[$itemType] ?? null;
        $trackedSetSlotId = $trackedReference['set_slot_id'] ?? null;
        $trackedSetSlot = is_null($trackedSetSlotId)
            ? null
            : SetSlot::with(['item', 'inventorySet'])->find($trackedSetSlotId);

        if (! is_null($trackedSetSlot) && (int) ($trackedSetSlot->inventorySet?->character_id ?? 0) !== (int) $character->id) {
            $trackedSetSlot = null;
            $trackedSetSlotId = null;
        }

        $trackedItem = $trackedSetSlot?->item ?? (is_null($trackedItemId) ? null : Item::find($trackedItemId));
        $keepBestDisposition = 'keep_best_' . $loserAction . '_rest';

        // Enchanting dedupes identical base+affix combinations onto the same Item row,
        // so re-crafting the same item type can hand back the exact item already
        // tracked as best. Treat that as "still the best, nothing changed" instead of
        // selling/destroying/disenchanting the retained slot and orphaning the item.
        if (! is_null($trackedItem) && (int) $trackedItem->id === (int) $newItem->id) {
            $keptItemDetails = $this->itemDetails($newItem, $trackedSetSlotId);
            $keptItemDetails['set_slot_id'] = $trackedSetSlotId;

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => $keepBestDisposition, 'kept_item' => $keptItemDetails],
            ];
        }

        if (is_null($trackedItem)) {
            $this->rememberKeepBestReference($batchCrafting, $newItem, $keepBestDisposition, null);

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => $keepBestDisposition, 'kept_item' => $this->itemDetails($newItem)],
            ];
        }

        $trackedLevel = (int) ($trackedItem->skill_level_required ?? 0);

        if ($newLevel >= $trackedLevel) {
            $loserResult = $this->applyKeepBestLoserAction($character, $trackedItem, $loserAction, $trackedSetSlotId);
            $this->rememberKeepBestReference($batchCrafting, $newItem, $keepBestDisposition, null);

            return [
                'counts' => $this->mergeCounts(['kept_count' => 1], $loserResult['counts']),
                'details' => array_merge(['disposition' => $keepBestDisposition, 'kept_item' => $this->itemDetails($newItem)], $loserResult['details']),
            ];
        }

        $loserResult = $this->applyKeepBestLoserAction($character, $newItem, $loserAction, null);

        return [
            'counts' => $loserResult['counts'],
            'details' => array_merge(['disposition' => $keepBestDisposition], $loserResult['details']),
        ];
    }

    private function applyKeepBestLoserAction(Character $character, Item $item, string $loserAction, ?int $setSlotId): array
    {
        if (! is_null($setSlotId)) {
            $setSlot = SetSlot::with(['item', 'inventorySet'])->find($setSlotId);

            if (! is_null($setSlot) && ! is_null($setSlot->item) && ! is_null($setSlot->inventorySet) && (int) $setSlot->inventorySet->character_id === (int) $character->id) {
                $itemDetails = $this->itemDetails($setSlot->item, $setSlot->id);
                $setSlotItem = $setSlot->item;

                return match ($loserAction) {
                    'disenchant' => $this->disenchantKeepBestSetSlot($character, $setSlot->inventorySet, $setSlot->id, $itemDetails),
                    'destroy' => $this->destroyKeepBestSetSlot($character, $setSlot->inventorySet, $setSlot->id, $itemDetails, $setSlotItem),
                    default => $this->sellKeepBestSetSlot($character, $setSlot->inventorySet, $setSlot->id, $itemDetails, $setSlotItem),
                };
            }
        }

        return match ($loserAction) {
            'disenchant' => $this->disenchantItem($character, $item),
            'destroy' => $this->destroyItem($character, $item),
            default => $this->sellItem($character, $item),
        };
    }

    private function sellKeepBestSetSlot(Character $character, InventorySet $set, int $setSlotId, ?array $itemDetails, Item $item): array
    {
        $goldBefore = $character->gold;
        $this->multiInventoryActionService->sellManySetSlots($character, $set, [$setSlotId]);
        $this->discardBatchClonedItemIfOrphaned($item);
        $goldGained = max(0, $character->refresh()->gold - $goldBefore);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'sell', 'sold_item' => $this->removedItemDetails($itemDetails, 'sold'), 'gold_gained' => $goldGained],
        ];
    }

    private function destroyKeepBestSetSlot(Character $character, InventorySet $set, int $setSlotId, ?array $itemDetails, Item $item): array
    {
        $this->multiInventoryActionService->destroyManySetSlots($character, $set, [$setSlotId]);
        $this->discardBatchClonedItemIfOrphaned($item);

        return [
            'counts' => ['destroyed_count' => 1],
            'details' => ['disposition' => 'destroy', 'destroyed_item' => $this->removedItemDetails($itemDetails, 'destroyed')],
        ];
    }

    private function disenchantKeepBestSetSlot(Character $character, InventorySet $set, int $setSlotId, ?array $itemDetails): array
    {
        $goldDustBefore = $character->gold_dust;
        $this->multiInventoryActionService->disenchantManySetSlots($character, $set, [$setSlotId]);
        $goldDustGained = max(0, $character->refresh()->gold_dust - $goldDustBefore);

        return [
            'counts' => ['disenchanted_count' => 1],
            'details' => ['disposition' => 'disenchant', 'disenchanted_item' => $this->removedItemDetails($itemDetails, 'disenchanted'), 'gold_dust_gained' => $goldDustGained],
        ];
    }

    private function applyKeepHighestItem(BatchCrafting $batchCrafting, Character $character, Item $newItem): array
    {
        $itemType = $newItem->type ?? 'weapon';
        $newLevel = (int) ($newItem->skill_level_required ?? 0);

        $progress = $batchCrafting->progress ?? [];
        $keepHighestItems = $progress['keep_highest_item_ids'] ?? [];
        $trackedItemId = $keepHighestItems[$itemType] ?? null;
        $trackedItem = is_null($trackedItemId) ? null : Item::find($trackedItemId);

        if (is_null($trackedItem)) {
            $keepHighestItems[$itemType] = $newItem->id;
            $batchCrafting->update(['progress' => array_merge($progress, ['keep_highest_item_ids' => $keepHighestItems])]);

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($newItem)],
            ];
        }

        $trackedLevel = (int) ($trackedItem->skill_level_required ?? 0);

        if ($newLevel >= $trackedLevel) {
            $sold = $this->sellItem($character, $trackedItem);
            $keepHighestItems[$itemType] = $newItem->id;
            $batchCrafting->update(['progress' => array_merge($progress, ['keep_highest_item_ids' => $keepHighestItems])]);

            return [
                'counts' => ['kept_count' => 1, 'sold_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($newItem), 'sold_item' => $sold['details']['sold_item'] ?? null, 'gold_gained' => $sold['details']['gold_gained'] ?? 0],
            ];
        }

        $sold = $this->sellItem($character, $newItem);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'keep_highest', 'sold_item' => $sold['details']['sold_item'] ?? null, 'gold_gained' => $sold['details']['gold_gained'] ?? 0],
        ];
    }

    private function sellItem(Character $character, Item $item): array
    {
        $itemDetails = $this->itemDetails($item);
        $goldGained = max(0, (int) SellItemCalculator::fetchSalePriceWithAffixes($item));
        $itemName = $item->affix_name ?? $item->name;

        $character->increment('gold', $goldGained);
        $this->discardBatchClonedItemIfOrphaned($item);

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Sold: ' . $itemName . ' for: ' . number_format($goldGained) . ' Gold.');

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'sell', 'sold_item' => $this->removedItemDetails($itemDetails, 'sold'), 'gold_gained' => $goldGained],
        ];
    }

    private function destroyItem(Character $character, Item $item): array
    {
        $itemDetails = $this->itemDetails($item);
        $itemName = $item->affix_name ?? $item->name;

        $this->discardBatchClonedItemIfOrphaned($item);

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Destroyed: ' . $itemName . '.');

        return [
            'counts' => ['destroyed_count' => 1],
            'details' => ['disposition' => 'destroy', 'destroyed_item' => $this->removedItemDetails($itemDetails, 'destroyed')],
        ];
    }

    /**
     * Dispatches the same DisenchantMany job the existing InventorySlot/SetSlot
     * disenchant paths use. The item is intentionally never deleted here: the
     * job runs on the queue and still needs to load it by id.
     */
    private function disenchantItem(Character $character, Item $item): array
    {
        $itemDetails = $this->itemDetails($item);
        $goldDustBefore = $character->gold_dust;

        DisenchantMany::dispatch($character, [$item->id]);

        $goldDustGained = max(0, $character->refresh()->gold_dust - $goldDustBefore);

        return [
            'counts' => ['disenchanted_count' => 1],
            'details' => [
                'disposition' => 'disenchant',
                'disenchanted_item' => $this->removedItemDetails($itemDetails, 'disenchanted'),
                'gold_dust_gained' => $goldDustGained,
            ],
        ];
    }

    /**
     * Uses the player-supplied listing price from progress.listing_price (set at
     * batch start, matching the manual List Item price the player was shown), floored
     * to the item's min sale price so the same rule manual listing enforces still applies.
     */
    private function listItem(BatchCrafting $batchCrafting, Character $character, Item $item): array
    {
        $requestedPrice = (int) (($batchCrafting->progress ?? [])['listing_price'] ?? 1);
        $minPrice = (int) SellItemCalculator::fetchMinPrice($item);
        $price = max($requestedPrice, $minPrice, 1);

        MarketBoard::create([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'listed_price' => $price,
        ]);

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Listed: ' . ($item->affix_name ?? $item->name) . ' for: ' . number_format($price) . ' Gold.');

        return [
            'counts' => ['listed_count' => 1],
            'details' => ['disposition' => 'list', 'listed_item' => $this->removedItemDetails($this->itemDetails($item), 'listed'), 'listed_price' => $price],
        ];
    }

    /**
     * Batch crafted items never sit in an InventorySlot/SetSlot until they are kept, so a
     * non-keep disposition (sell/destroy/disenchant) must clean up an enchanted clone here
     * instead of relying on a slot delete. Base catalog items (no prefix/suffix) are never
     * deleted since other slots/characters may reference the same catalog row.
     */
    private function discardBatchClonedItemIfOrphaned(Item $item): void
    {
        if (is_null($item->item_prefix_id) && is_null($item->item_suffix_id)) {
            return;
        }

        if ($item->inventorySlots()->doesntExist()
            && $item->inventorySetSlots()->doesntExist()
            && $item->marketListings()->doesntExist()
            && $item->marketHistory()->doesntExist()) {
            $item->delete();
        }
    }

    private function findProducedAlchemySlot(Character $character, array $amountsBefore): ?AlchemyBagSlot
    {
        return AlchemyBagSlot::where('character_id', $character->id)
            ->with('item')
            ->get()
            ->first(function (AlchemyBagSlot $slot) use ($amountsBefore) {
                return $slot->amount > ($amountsBefore[$slot->item_id] ?? 0);
            });
    }

    private function sellAlchemySlot(Character $character, AlchemyBagSlot $slot): array
    {
        $itemDetails = $this->itemDetails($slot->item, $slot->id);
        $goldGained = (int) SellItemCalculator::fetchSalePriceWithAffixes($slot->item);

        $character->update(['gold' => $character->gold + $goldGained]);
        $this->decrementAlchemySlot($slot);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'sell', 'sold_item' => $this->removedItemDetails($itemDetails, 'sold'), 'gold_gained' => $goldGained],
        ];
    }

    private function destroyAlchemySlot(AlchemyBagSlot $slot): array
    {
        $itemDetails = $this->itemDetails($slot->item, $slot->id);
        $this->decrementAlchemySlot($slot);

        return [
            'counts' => ['destroyed_count' => 1],
            'details' => ['disposition' => 'destroy', 'destroyed_item' => $this->removedItemDetails($itemDetails, 'destroyed')],
        ];
    }

    private function listAlchemySlot(BatchCrafting $batchCrafting, Character $character, AlchemyBagSlot $slot): array
    {
        $itemDetails = $this->itemDetails($slot->item, $slot->id);
        $requestedPrice = (int) (($batchCrafting->progress ?? [])['listing_price'] ?? 1);
        $minPrice = (int) SellItemCalculator::fetchMinPrice($slot->item);
        $price = max($requestedPrice, $minPrice, 1);

        MarketBoard::create([
            'character_id' => $character->id,
            'item_id' => $slot->item_id,
            'listed_price' => $price,
        ]);

        $this->decrementAlchemySlot($slot);

        return [
            'counts' => ['listed_count' => 1],
            'details' => ['disposition' => 'list', 'listed_item' => $this->removedItemDetails($itemDetails, 'listed'), 'listed_price' => $price],
        ];
    }

    private function decrementAlchemySlot(AlchemyBagSlot $slot): void
    {
        if ($slot->amount <= 1) {
            $slot->delete();

            return;
        }

        $slot->update(['amount' => $slot->amount - 1]);
    }

    private function itemDetails(?Item $item, ?int $slotId = null, bool $canView = true, ?int $alchemySlotId = null): ?array
    {
        if (is_null($item)) {
            return null;
        }

        return [
            'slot_id' => $slotId,
            'alchemy_slot_id' => $alchemySlotId,
            'item_id' => $item->id,
            'item_id_for_modal' => $canView ? $item->id : null,
            'slot_id_for_modal' => $canView ? $slotId : null,
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
            'affix_count' => $item->affix_count ?? 0,
            'is_unique' => (bool) ($item->is_unique ?? false),
            'holy_stacks_applied' => $item->holy_stacks_applied ?? 0,
            'is_mythic' => (bool) ($item->is_mythic ?? false),
            'is_cosmic' => (bool) ($item->is_cosmic ?? false),
            'can_view' => $canView && ! is_null($slotId),
            'crafted_at' => now()->toJSON(),
            'full_item_details' => (new ItemTransformer())->transform($item),
        ];
    }

    private function craftingSkillSnapshot(Character $character, string $craftingType): ?array
    {
        return $this->skillSnapshot($character, $this->craftingSkillName($craftingType), false);
    }

    private function craftingSkillName(string $craftingType): string
    {
        if (in_array($craftingType, ['dagger', 'sword', 'claw', 'wand', 'censer', 'stave', 'hammer', 'bow', 'gun', 'fan', 'mace', 'scratch-awl', 'weapon'], true)) {
            return 'Weapon Crafting';
        }

        return match ($craftingType) {
            'armour', 'helmet', 'body', 'leggings', 'sleeves', 'gloves', 'shield', 'feet' => 'Armour Crafting',
            'ring' => 'Ring Crafting',
            'spell', 'spell_damage', 'spell_healing' => 'Spell Crafting',
            default => 'Weapon Crafting',
        };
    }

    private function skillSnapshot(Character $character, string $lookup, bool $byType): ?array
    {
        $skill = Skill::where('character_id', $character->id)
            ->whereHas('baseSkill', function ($query) use ($lookup, $byType) {
                if ($byType) {
                    $query->where('type', $lookup);

                    return;
                }

                $query->where('name', $lookup);
            })
            ->with('baseSkill')
            ->first();

        if (is_null($skill)) {
            return null;
        }

        return [
            'level' => (int) $skill->level,
            'xp' => (int) $skill->xp,
            'xp_max' => (int) $skill->xp_max,
        ];
    }

    private function skillExperienceGained(?array $before, ?array $after): int
    {
        if (is_null($before) || is_null($after)) {
            return 0;
        }

        if ($after['level'] > $before['level']) {
            return max(0, ((int) $before['xp_max'] - (int) $before['xp']) + (int) $after['xp']);
        }

        return max(0, (int) $after['xp'] - (int) $before['xp']);
    }

    private function acceptedEventEnchantExperience(Character $character, InventorySlot|GlobalEventCraftingInventorySlot $slot): int
    {
        $remainingSlot = $slot instanceof GlobalEventCraftingInventorySlot
            ? GlobalEventCraftingInventorySlot::find($slot->id)
            : InventorySlot::find($slot->id);

        if (! is_null($remainingSlot)) {
            return 0;
        }

        $skill = Skill::where('character_id', $character->id)
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->with('baseSkill')
            ->first();

        if (is_null($skill)) {
            return 0;
        }

        $xp = SkillXPCalculator::fetchSkillXP($skill);
        $gameMapBonus = $character->map?->gameMap?->skill_training_bonus ?? 0;

        return (int) floor($xp + ($xp * $gameMapBonus));
    }

    private function removedItemDetails(?array $itemDetails, string $status): ?array
    {
        if (is_null($itemDetails)) {
            return null;
        }

        return array_merge($itemDetails, [
            'status' => $status,
            'can_view' => false,
            'item_id_for_modal' => null,
            'slot_id_for_modal' => null,
        ]);
    }

    private function currencyDetails(array $before, Character $character): array
    {
        return [
            'before' => $before,
            'current' => [
                'gold' => $character->gold,
                'gold_dust' => $character->gold_dust,
                'shards' => $character->shards,
            ],
        ];
    }

    private function mergeCounts(array $counts, array $additions): array
    {
        foreach ($additions as $column => $increment) {
            $counts[$column] = ($counts[$column] ?? 0) + $increment;
        }

        return $counts;
    }
}
