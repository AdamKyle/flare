<?php

namespace App\Game\BatchCrafting\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\MarketBoard;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use App\Game\Character\CharacterInventory\Values\ArmourType;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\NpcActions\WorkBench\Services\HolyItemService;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Game\Skills\Values\SkillTypeValue;
use Facades\App\Flare\Calculators\SellItemCalculator;

class BatchCraftingProcessor
{
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly AlchemyService $alchemyService,
        private readonly TrinketCraftingService $trinketCraftingService,
        private readonly EnchantingService $enchantingService,
        private readonly HolyItemService $holyItemService,
        private readonly MultiInventoryActionService $multiInventoryActionService,
    ) {}

    public function processOneTick(BatchCrafting $batchCrafting, Character $character): array
    {
        $type = BatchCraftingType::from($batchCrafting->batch_type);
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);

        return match ($type) {
            BatchCraftingType::CRAFT => $this->processCraft($batchCrafting, $character, $disposition),
            BatchCraftingType::CRAFT_AND_ENCHANT => $this->processCraftAndEnchant($batchCrafting, $character, $disposition),
            BatchCraftingType::ENCHANT => $this->processEnchant($batchCrafting, $character, $disposition),
            BatchCraftingType::ALCHEMY => $this->processAlchemy($batchCrafting, $character, $disposition),
            BatchCraftingType::HOLY_OILS => $this->processHolyOils($batchCrafting, $character),
            BatchCraftingType::TRINKETRY => $this->processTrinketry($batchCrafting, $character, $disposition),
        };
    }

    private function processCraft(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $craftMode = $progress['craft_mode'] ?? 'full_set';
        $queue = $progress['craft_queue'] ?? null;

        if ($craftMode === 'experience') {
            return $this->processCraftExperience($batchCrafting, $character, $disposition, $progress);
        }

        if ($craftMode === 'specific_item') {
            return $this->processCraftSpecificItem($batchCrafting, $character, $disposition, $progress);
        }

        if (is_null($queue)) {
            $queue = $this->buildFullSetQueue($character);

            $remainingSpace = $character->inventory_max - $character->getInventoryCount();

            if (count($queue) > 0 && $remainingSpace < count($queue)) {
                $progress['no_inventory_reason'] = 'Full set requires ' . count($queue) . ' free inventory slots. Current remaining space: ' . $remainingSpace . '.';
                $batchCrafting->update(['progress' => $progress]);

                return ['end_reason' => BatchCraftingEndReason::NO_INVENTORY_SPACE, 'actions' => [[
                    'action' => 'craft',
                    'status' => 'failed',
                    'failure' => $progress['no_inventory_reason'],
                ]]];
            }

            $progress['craft_queue'] = $queue;
            $progress['craft_queue_index'] = 0;
            $progress['requested_set_count'] = (int) ($progress['requested_set_count'] ?? $progress['set_count'] ?? 1);
            $progress['completed_set_count'] = (int) ($progress['completed_set_count'] ?? 0);
            $batchCrafting->update(['progress' => $progress]);
        }

        $index = (int) ($progress['craft_queue_index'] ?? 0);

        if (empty($queue)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        if ($index >= count($queue)) {
            $progress['completed_set_count'] = ((int) ($progress['completed_set_count'] ?? 0)) + 1;

            if ($progress['completed_set_count'] >= (int) ($progress['requested_set_count'] ?? 1)) {
                $batchCrafting->update(['progress' => $progress]);

                return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED, 'actions' => [[
                    'action' => 'craft',
                    'status' => 'completed',
                    'completed_set_count' => $progress['completed_set_count'],
                ]]];
            }

            $progress['craft_queue'] = null;
            $progress['craft_queue_index'] = 0;
            $batchCrafting->update(['progress' => $progress]);

            return ['actions' => [[
                'action' => 'craft',
                'status' => 'skipped',
                'completed_set_count' => $progress['completed_set_count'],
                'failure' => 'Current full set completed. Next tick will start the next set.',
            ]]];
        }

        $target = $queue[$index];
        $craftingType = $target['crafting_type'];
        $targetType = $target['type'];

        $craftableItems = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => $craftingType,
        ], false);

        $craftableItem = $craftableItems->first(fn ($i) => $i->type === $targetType);
        $item = is_null($craftableItem) ? null : Item::find($craftableItem->id);

        $progress['craft_queue_index'] = $index + 1;
        $batchCrafting->update(['progress' => $progress]);

        if (is_null($item)) {
            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'craft',
                'status' => 'skipped',
                'failure' => 'No craftable item found for type: ' . $targetType,
            ]]];
        }

        $crafted = $this->craftingService->craft($character->refresh(), [
            'item_to_craft' => $item->id,
            'type' => $craftingType,
            'craft_for_npc' => false,
            'craft_for_event' => false,
            'skip_crafting_timeout' => true,
        ]);

        $slotId = $this->craftingService->getLastCraftedInventorySlotId();

        if ($crafted && ! is_null($slotId)) {
            $action = ['action' => 'craft', 'crafted_item' => $this->itemDetails($item, $slotId)];
            $counts = ['crafted_count' => 1];
            $dispositionResult = $this->applyDisposition($batchCrafting, $character->refresh(), $slotId, $disposition);
            $counts = $this->mergeCounts($counts, $dispositionResult['counts']);
            $action = array_merge($action, $dispositionResult['details']);
        } else {
            $counts = ['failed_count' => 1];
            $action = [
                'action' => 'craft',
                'failure' => 'Crafting service did not produce an inventory slot.',
            ];
        }

        return ['counts' => $counts, 'actions' => [$action]];
    }

    private function processCraftAndEnchant(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $craftMode = $progress['craft_mode'] ?? 'full_set';

        if ($craftMode === 'experience') {
            return $this->processCraftExperience($batchCrafting, $character, $disposition, $progress);
        }

        if ($craftMode === 'specific_item') {
            return $this->processCraftAndEnchantSpecificItem($batchCrafting, $character, $disposition, $progress);
        }

        $phase = $progress['craft_enchant_phase'] ?? 'craft';

        if ($phase === 'enchant') {
            return $this->processCraftAndEnchantEnchantPhase($batchCrafting, $character, $disposition, $progress);
        }

        return $this->processCraftAndEnchantCraftPhase($batchCrafting, $character, $progress);
    }

    private function processCraftAndEnchantSpecificItem(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
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

        $crafted = $this->craftingService->craft($character->refresh(), [
            'item_to_craft' => $item->id,
            'type' => $craftingType,
            'craft_for_npc' => false,
            'craft_for_event' => false,
            'skip_crafting_timeout' => true,
        ]);

        $slotId = $this->craftingService->getLastCraftedInventorySlotId();

        if (! $crafted || is_null($slotId)) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'failed',
                'failure' => 'Crafting service did not produce an inventory slot.',
            ]]];
        }

        $progress['craft_enchant_phase'] = 'enchant';
        $progress['pending_enchant_slot_id'] = $slotId;
        $batchCrafting->update(['progress' => $progress]);

        return ['counts' => ['crafted_count' => 1], 'actions' => [[
            'action' => 'craft_and_enchant',
            'status' => 'crafted',
            'crafted_item' => $this->itemDetails($item, $slotId),
        ]]];
    }

    private function processCraftAndEnchantCraftPhase(BatchCrafting $batchCrafting, Character $character, array $progress): array
    {
        $queue = $progress['craft_enchant_queue'] ?? null;

        if (is_null($queue)) {
            $queue = $this->buildFullSetQueue($character);

            $remainingSpace = $character->inventory_max - $character->getInventoryCount();

            if (count($queue) > 0 && $remainingSpace < count($queue)) {
                $progress['no_inventory_reason'] = 'Full set craft and enchant requires ' . count($queue) . ' free inventory slots. Current remaining space: ' . $remainingSpace . '.';
                $batchCrafting->update(['progress' => $progress]);

                return ['end_reason' => BatchCraftingEndReason::NO_INVENTORY_SPACE, 'actions' => [[
                    'action' => 'craft_and_enchant',
                    'status' => 'failed',
                    'failure' => $progress['no_inventory_reason'],
                ]]];
            }

            $progress['craft_enchant_queue'] = $queue;
            $progress['craft_enchant_index'] = 0;
            $progress['requested_set_count'] = (int) ($progress['requested_set_count'] ?? $progress['set_count'] ?? 5);
            $progress['completed_set_count'] = (int) ($progress['completed_set_count'] ?? 0);
            $batchCrafting->update(['progress' => $progress]);
        }

        $index = (int) ($progress['craft_enchant_index'] ?? 0);

        if (empty($queue)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        if ($index >= count($queue)) {
            $progress['completed_set_count'] = ((int) ($progress['completed_set_count'] ?? 0)) + 1;

            if ($progress['completed_set_count'] >= (int) ($progress['requested_set_count'] ?? 5)) {
                $batchCrafting->update(['progress' => $progress]);

                return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED, 'actions' => [[
                    'action' => 'craft_and_enchant',
                    'status' => 'completed',
                    'completed_set_count' => $progress['completed_set_count'],
                ]]];
            }

            $progress['craft_enchant_queue'] = null;
            $progress['craft_enchant_index'] = 0;
            $batchCrafting->update(['progress' => $progress]);

            return ['actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'skipped',
                'completed_set_count' => $progress['completed_set_count'],
                'failure' => 'Current full set completed. Next tick will start the next set.',
            ]]];
        }

        $target = $queue[$index];
        $craftingType = $target['crafting_type'];
        $targetType = $target['type'];

        $craftableItems = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => $craftingType,
        ], false);

        $craftableItem = $craftableItems->first(fn ($i) => $i->type === $targetType);
        $item = is_null($craftableItem) ? null : Item::find($craftableItem->id);

        if (is_null($item)) {
            $progress['craft_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'skipped',
                'failure' => 'No craftable item found for type: ' . $targetType,
            ]]];
        }

        $crafted = $this->craftingService->craft($character->refresh(), [
            'item_to_craft' => $item->id,
            'type' => $craftingType,
            'craft_for_npc' => false,
            'craft_for_event' => false,
            'skip_crafting_timeout' => true,
        ]);

        $slotId = $this->craftingService->getLastCraftedInventorySlotId();

        if (! $crafted || is_null($slotId)) {
            $progress['craft_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'failure' => 'Crafting service did not produce an inventory slot.',
            ]]];
        }

        $progress['craft_enchant_phase'] = 'enchant';
        $progress['pending_enchant_slot_id'] = $slotId;
        $batchCrafting->update(['progress' => $progress]);

        return ['counts' => ['crafted_count' => 1], 'actions' => [[
            'action' => 'craft_and_enchant',
            'status' => 'crafted',
            'crafted_item' => $this->itemDetails($item, $slotId),
        ]]];
    }

    private function processCraftAndEnchantEnchantPhase(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $slotId = $progress['pending_enchant_slot_id'] ?? null;
        $index = (int) ($progress['craft_enchant_index'] ?? 0);

        $progress['craft_enchant_phase'] = 'craft';
        $progress['pending_enchant_slot_id'] = null;
        $progress['craft_enchant_index'] = $index + 1;
        $batchCrafting->update(['progress' => $progress]);

        if (is_null($slotId)) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'failed',
                'failure' => 'No pending slot to enchant.',
            ]]];
        }

        $slot = InventorySlot::find($slotId);

        if (is_null($slot)) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'failed',
                'failure' => 'Crafted slot was removed before enchanting.',
            ]]];
        }

        $action = [
            'action' => 'craft_and_enchant',
            'crafted_item' => $this->itemDetails($slot->item, $slot->id),
        ];

        if ($this->tryEnchantSlot($character, $slot, $progress['enchant_affix_ids'] ?? null)) {
            $slot = $slot->refresh();
            $action['enchanted_item'] = $this->itemDetails($slot->item, $slot->id);
            $counts = ['enchanted_count' => 1];
        } else {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'failed',
                'crafted_item' => $this->itemDetails($slot->item, $slot->id),
                'failure' => 'Enchanting service did not apply an enchantment.',
            ]]];
        }

        $dispositionResult = $this->applyDisposition($batchCrafting, $character->refresh(), $slotId, $disposition);
        $counts = $this->mergeCounts($counts, $dispositionResult['counts']);
        $action = array_merge($action, $dispositionResult['details']);

        return ['counts' => $counts, 'actions' => [$action]];
    }

    private function processEnchant(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $slot = $this->pickEnchantableSlot($character);

        if (is_null($slot)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $enchanted = $this->tryEnchantSlot($character, $slot);

        if ($enchanted) {
            $action = [
                'action' => 'enchant',
                'enchanted_item' => $this->itemDetails($slot->item, $slot->id),
            ];
            $counts = ['enchanted_count' => 1];
            $dispositionResult = $this->applyDisposition($batchCrafting, $character->refresh(), $slot->id, $disposition);
            $counts = $this->mergeCounts($counts, $dispositionResult['counts']);
            $action = array_merge($action, $dispositionResult['details']);
        } else {
            $counts = ['failed_count' => 1];
            $action = [
                'action' => 'enchant',
                'enchanted_item' => $this->itemDetails($slot->item, $slot->id),
                'failure' => 'Enchanting service did not apply an enchantment.',
            ];
        }

        return ['counts' => $counts, 'actions' => [$action]];
    }

    private function processAlchemy(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $alchemyMode = $progress['alchemy_mode'] ?? 'standard';

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
        }

        $items = $this->alchemyService->fetchAlchemistItems($character, false);

        if ($items->isEmpty()) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        if (! $character->canAddToAlchemyBag(1)) {
            return ['end_reason' => BatchCraftingEndReason::NO_INVENTORY_SPACE, 'actions' => [[
                'action' => 'alchemy',
                'status' => 'failed',
                'failure' => 'Alchemy bag does not have enough space.',
            ]]];
        }

        $item = $alchemyMode === 'experience'
            ? $items->sortByDesc('skill_level_required')->first()
            : $items->first();
        $slotAmountsBefore = AlchemyBagSlot::where('character_id', $character->id)
            ->pluck('amount', 'item_id')
            ->all();
        $currencyBefore = [
            'gold' => $character->gold,
            'gold_dust' => $character->gold_dust,
            'shards' => $character->shards,
        ];

        $this->alchemyService->transmute($character, $item->id);
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
            'alchemy_item' => $this->itemDetails($producedSlot->item, $producedSlot->id),
            'currency' => $this->currencyDetails($currencyBefore, $character->refresh()),
        ] + $dispositionResult['details']]];
    }

    private function applyAlchemyDisposition(BatchCrafting $batchCrafting, Character $character, AlchemyBagSlot $slot, BatchCraftingDisposition $disposition): array
    {
        return match ($disposition) {
            BatchCraftingDisposition::KEEP => [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $this->itemDetails($slot->item, $slot->id)],
            ],
            BatchCraftingDisposition::KEEP_HIGHEST => $this->applyAlchemyKeepHighest($batchCrafting, $character, $slot),
            BatchCraftingDisposition::SELL => $this->sellAlchemySlot($character, $slot),
            BatchCraftingDisposition::DESTROY => $this->destroyAlchemySlot($slot),
            BatchCraftingDisposition::LIST => $this->listAlchemySlot($character, $slot),
            BatchCraftingDisposition::DISENCHANT => [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $this->itemDetails($slot->item, $slot->id, true, $slot->id)],
            ],
        };
    }

    private function applyAlchemyKeepHighest(BatchCrafting $batchCrafting, Character $character, AlchemyBagSlot $slot): array
    {
        $progress = $batchCrafting->progress ?? [];
        $trackedSlotId = $progress['alchemy_keep_highest_slot'] ?? null;

        if (is_null($trackedSlotId)) {
            $batchCrafting->update(['progress' => array_merge($progress, ['alchemy_keep_highest_slot' => $slot->id])]);

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($slot->item, $slot->id)],
            ];
        }

        $trackedSlot = AlchemyBagSlot::where('character_id', $character->id)->find($trackedSlotId);

        if (is_null($trackedSlot) || is_null($trackedSlot->item)) {
            $batchCrafting->update(['progress' => array_merge($progress, ['alchemy_keep_highest_slot' => $slot->id])]);

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($slot->item, $slot->id)],
            ];
        }

        $newLevel = (int) ($slot->item->skill_level_required ?? 0);
        $trackedLevel = (int) ($trackedSlot->item->skill_level_required ?? 0);

        if ($newLevel >= $trackedLevel) {
            $sold = $this->sellAlchemySlot($character, $trackedSlot);
            $batchCrafting->update(['progress' => array_merge($progress, ['alchemy_keep_highest_slot' => $slot->id])]);

            return [
                'counts' => ['kept_count' => 1, 'sold_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($slot->item, $slot->id), 'sold_item' => $sold['details']['sold_item'] ?? null, 'gold_gained' => $sold['details']['gold_gained'] ?? 0],
            ];
        }

        $sold = $this->sellAlchemySlot($character, $slot);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'keep_highest', 'sold_item' => $sold['details']['sold_item'] ?? null, 'gold_gained' => $sold['details']['gold_gained'] ?? 0],
        ];
    }

    private function processHolyOils(BatchCrafting $batchCrafting, Character $character): array
    {
        $selectedItems = $batchCrafting->selected_items ?? [];
        $selectedOils = $batchCrafting->selected_oils ?? [];

        $inventory = $character->inventory;

        $selectedItems = array_values(array_filter($selectedItems, function (int $itemId) use ($inventory) {
            if (is_null($inventory)) {
                return false;
            }

            $slot = $inventory->slots()->where('item_id', $itemId)->first();

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

        $itemId = $selectedItems[0];
        $oilSlotId = $selectedOils[0];

        $targetSlot = is_null($inventory) ? null : $inventory->slots()->where('item_id', $itemId)->with('item')->first();
        $oilSlot = AlchemyBagSlot::where('id', $oilSlotId)->where('character_id', $character->id)->with('item')->first();

        if (! is_null($targetSlot) && ! is_null($targetSlot->item) && ! is_null($oilSlot) && ! is_null($oilSlot->item)) {
            $oilCost = $this->holyItemService->getCost($targetSlot->item, $oilSlot->item);

            if ($oilCost > $character->gold_dust) {
                return ['end_reason' => BatchCraftingEndReason::NO_GOLD_DUST];
            }
        }

        $targetItemSnapshot = is_null($targetSlot) ? null : $this->itemDetails($targetSlot->item, $targetSlot->id);
        $oilItemSnapshot = is_null($oilSlot) ? null : $this->itemDetails($oilSlot->item, $oilSlot->id);

        $existingSlotIds = is_null($inventory) ? [] : $inventory->slots()->pluck('id')->all();

        $result = $this->holyItemService->applyOil($character, [
            'item_id' => $itemId,
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
                    'item_id' => $itemId,
                    'oil_slot_id' => $oilSlotId,
                ],
            ]]];
        }

        $character = $character->refresh();

        $originalItemInInventory = ! is_null($character->inventory)
            && $character->inventory->slots()->where('item_id', $itemId)->exists();

        if (! $originalItemInInventory) {
            $newSlot = $character->inventory?->slots()->whereNotIn('id', $existingSlotIds)->first();

            if (! is_null($newSlot) && ! is_null($newSlot->item) && ($newSlot->item->holy_stacks - $newSlot->item->holy_stacks_applied) > 0) {
                $newItemId = $newSlot->item_id;
                $selectedItems = array_map(fn (int $id) => $id === $itemId ? $newItemId : $id, $selectedItems);
            } else {
                $selectedItems = array_values(array_filter($selectedItems, fn (int $id) => $id !== $itemId));
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

        if (empty($selectedOils)) {
            $progress['all_oils_applied'] = true;
            $batchCrafting->update(['progress' => $progress]);
        }

        return ['counts' => ['applied_count' => 1], 'actions' => [[
            'action' => 'holy_oil',
            'oil_application' => [
                'target_item' => $targetItemSnapshot,
                'oil_item' => $oilItemSnapshot,
                'item_id' => $itemId,
                'oil_slot_id' => $oilSlotId,
            ],
        ]]];
    }

    private function processTrinketry(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $trinketryMode = $progress['trinketry_mode'] ?? 'amount';
        $craftAmount = (int) ($progress['trinketry_amount'] ?? $progress['craft_amount'] ?? 0);
        $craftedSoFar = (int) ($progress['trinketry_amount_count'] ?? 0);

        if ($trinketryMode === 'experience') {
            $skill = $character->skills()
                ->whereHas('baseSkill', fn ($q) => $q->where('name', 'Trinketry'))
                ->with('baseSkill')
                ->first();

            if (! is_null($skill) && $skill->level >= $skill->max_level) {
                return ['end_reason' => BatchCraftingEndReason::SKILL_MAXED];
            }
        }

        if ($craftAmount > 0 && $craftedSoFar >= $craftAmount) {
            return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED];
        }

        $items = $this->trinketCraftingService->fetchItemsToCraft($character, false);

        if (empty($items)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $itemId = collect($items)->sortByDesc('skill_level_required')->first()['id'] ?? null;
        $item = is_null($itemId) ? null : Item::find($itemId);

        if (is_null($item)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $inventoryBefore = $character->getInventoryCount();
        $this->trinketCraftingService->craft($character, $item);
        $character = $character->refresh();

        if ($character->getInventoryCount() <= $inventoryBefore) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'trinketry',
                'failure' => 'Trinketry service did not add an item to inventory.',
            ]]];
        }

        $slotId = $character->inventory->slots()
            ->where('equipped', false)
            ->orderByDesc('id')
            ->value('id');

        $counts = ['crafted_count' => 1];

        if ($craftAmount > 0) {
            $progress['trinketry_amount_count'] = $craftedSoFar + 1;
            $batchCrafting->update(['progress' => $progress]);
        }

        if (! is_null($slotId)) {
            $dispositionResult = $this->applyDisposition($batchCrafting, $character, $slotId, $disposition);
            $counts = array_merge($counts, $dispositionResult['counts']);
            $actions = [[
                'action' => 'trinketry',
                'trinketry_item' => $this->itemDetails($item, $slotId),
            ] + $dispositionResult['details']];
        } else {
            $counts['kept_count'] = 1;
            $actions = [[
                'action' => 'trinketry',
                'trinketry_item' => $this->itemDetails($item),
                'disposition' => 'keep',
            ]];
        }

        return ['counts' => $counts, 'actions' => $actions];
    }

    private function processCraftExperience(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $craftingType = $progress['specific_crafting_type'] ?? 'weapon';

        $normalizedType = $craftingType;

        if (in_array($craftingType, \App\Game\Character\CharacterInventory\Values\ItemType::validWeapons())) {
            $normalizedType = 'weapon';
        }

        $gameSkillName = match ($normalizedType) {
            'ring' => 'Ring Crafting',
            'spell', 'spell_damage', 'spell_healing' => 'Spell Crafting',
            'armour' => 'Armour Crafting',
            default => ucfirst($normalizedType) . ' Crafting',
        };

        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($q) => $q->where('name', $gameSkillName))
            ->with('baseSkill')
            ->first();

        if (! is_null($skill) && $skill->level >= $skill->max_level) {
            return ['end_reason' => BatchCraftingEndReason::SKILL_MAXED];
        }

        $craftableItems = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => $craftingType,
        ], false);

        if ($craftableItems->isEmpty()) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $craftableItem = $craftableItems->sortByDesc('skill_level_required')->first();
        $item = is_null($craftableItem) ? null : Item::find($craftableItem->id);

        if (is_null($item)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        return $this->craftItem($batchCrafting, $character, $disposition, $item, $craftingType);
    }

    private function processCraftSpecificItem(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $craftAmount = (int) ($progress['craft_amount'] ?? 0);
        $craftedSoFar = (int) ($progress['craft_specific_count'] ?? 0);

        if ($craftAmount > 0 && $craftedSoFar >= $craftAmount) {
            return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED];
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

        $result = $this->craftItem($batchCrafting, $character, $disposition, $item, $craftingType);

        if (isset($result['counts']['crafted_count']) && $result['counts']['crafted_count'] > 0) {
            $progress['craft_specific_count'] = $craftedSoFar + 1;
            $batchCrafting->update(['progress' => $progress]);
        }

        return $result;
    }

    private function craftItem(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, Item $item, string $craftingType): array
    {
        $crafted = $this->craftingService->craft($character->refresh(), [
            'item_to_craft' => $item->id,
            'type' => $craftingType,
            'craft_for_npc' => false,
            'craft_for_event' => false,
            'skip_crafting_timeout' => true,
        ]);

        $slotId = $this->craftingService->getLastCraftedInventorySlotId();

        if ($crafted && ! is_null($slotId)) {
            $action = ['action' => 'craft', 'crafted_item' => $this->itemDetails($item, $slotId)];
            $counts = ['crafted_count' => 1];
            $dispositionResult = $this->applyDisposition($batchCrafting, $character->refresh(), $slotId, $disposition);
            $counts = $this->mergeCounts($counts, $dispositionResult['counts']);
            $action = array_merge($action, $dispositionResult['details']);
        } else {
            $counts = ['failed_count' => 1];
            $action = [
                'action' => 'craft',
                'failure' => 'Crafting service did not produce an inventory slot.',
            ];
        }

        return ['counts' => $counts, 'actions' => [$action]];
    }

    private function buildFullSetQueue(Character $character): array
    {
        $targets = array_merge(
            array_map(fn (string $w) => ['type' => $w, 'crafting_type' => $w], ItemType::validWeapons()),
            [
                ['type' => ItemType::RING->value, 'crafting_type' => 'ring'],
                ['type' => ItemType::RING->value, 'crafting_type' => 'ring'],
                ['type' => ItemType::SPELL_DAMAGE->value, 'crafting_type' => 'spell'],
                ['type' => ItemType::SPELL_HEALING->value, 'crafting_type' => 'spell'],
            ],
            array_map(fn (string $a) => ['type' => $a, 'crafting_type' => 'armour'], ArmourType::allTypes()),
        );

        $craftableCache = [];
        $queue = [];

        foreach ($targets as $target) {
            $cacheKey = $target['crafting_type'];

            if (! isset($craftableCache[$cacheKey])) {
                try {
                    $craftableCache[$cacheKey] = $this->craftingService->fetchCraftableItems($character, [
                        'crafting_type' => $cacheKey,
                    ], false);
                } catch (\Throwable) {
                    $craftableCache[$cacheKey] = collect();
                }
            }

            if ($craftableCache[$cacheKey]->first(fn ($i) => $i->type === $target['type']) !== null) {
                $queue[] = $target;
            }
        }

        return $queue;
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
                    ->orWhereNull('item_suffix_id');
            })
            ->first();
    }

    private function tryEnchantSlot(Character $character, InventorySlot $slot, ?array $affixIds = null): bool
    {
        $enchantingSkill = $character->skills()
            ->whereHas('baseSkill', function ($query) {
                $query->where('type', SkillTypeValue::ENCHANTING->value);
            })
            ->first();

        if (is_null($enchantingSkill)) {
            return false;
        }

        $affixIds = array_values(array_filter($affixIds ?? [], fn ($affixId) => ! is_null($affixId)));

        if (empty($affixIds)) {
            $affix = ItemAffix::where('skill_level_required', '<=', $enchantingSkill->level)
                ->orderBy('cost', 'asc')
                ->first();

            if (is_null($affix)) {
                return false;
            }

            $affixIds = [$affix->id];
        }

        $cost = $this->enchantingService->getCostOfEnchantment($character, $affixIds, $slot->item_id);

        if ($character->gold < $cost) {
            return false;
        }

        $this->enchantingService->enchant($character, [
            'affix_ids' => $affixIds,
            'enchant_for_event' => false,
        ], $slot, $cost);

        return true;
    }

    private function applyDisposition(BatchCrafting $batchCrafting, Character $character, int $slotId, BatchCraftingDisposition $disposition): array
    {
        return match ($disposition) {
            BatchCraftingDisposition::KEEP => [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $this->inventorySlotDetails($character, $slotId)],
            ],
            BatchCraftingDisposition::KEEP_HIGHEST => $this->applyKeepHighest($batchCrafting, $character, $slotId),
            BatchCraftingDisposition::SELL => $this->sellSlot($character, $slotId),
            BatchCraftingDisposition::DESTROY => $this->destroySlot($character, $slotId),
            BatchCraftingDisposition::LIST => $this->listSlot($character, $slotId),
            BatchCraftingDisposition::DISENCHANT => $this->disenchantSlot($character, $slotId),
            BatchCraftingDisposition::KEEP_BEST_SELL_REST => $this->applyKeepBestAndRest($batchCrafting, $character, $slotId, 'sell'),
            BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST => $this->applyKeepBestAndRest($batchCrafting, $character, $slotId, 'disenchant'),
        };
    }

    private function applyKeepBestAndRest(BatchCrafting $batchCrafting, Character $character, int $slotId, string $loserAction): array
    {
        $newSlot = $character->inventory->slots()->find($slotId);

        if (is_null($newSlot) || is_null($newSlot->item)) {
            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_best_' . $loserAction . '_rest'],
            ];
        }

        $itemType = $newSlot->item->type ?? 'weapon';
        $newLevel = (int) ($newSlot->item->skill_level_required ?? 0);
        $progress = $batchCrafting->progress ?? [];
        $keepBestSlots = $progress['keep_best_slots'] ?? [];
        $trackedSlotId = $keepBestSlots[$itemType] ?? null;

        if (is_null($trackedSlotId)) {
            $keepBestSlots[$itemType] = $slotId;
            $batchCrafting->update(['progress' => array_merge($progress, ['keep_best_slots' => $keepBestSlots])]);

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_best_' . $loserAction . '_rest', 'kept_item' => $this->itemDetails($newSlot->item, $slotId)],
            ];
        }

        $trackedSlot = $character->inventory->slots()->find($trackedSlotId);

        if (is_null($trackedSlot) || is_null($trackedSlot->item)) {
            $keepBestSlots[$itemType] = $slotId;
            $batchCrafting->update(['progress' => array_merge($progress, ['keep_best_slots' => $keepBestSlots])]);

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_best_' . $loserAction . '_rest', 'kept_item' => $this->itemDetails($newSlot->item, $slotId)],
            ];
        }

        $trackedLevel = (int) ($trackedSlot->item->skill_level_required ?? 0);

        if ($newLevel >= $trackedLevel) {
            $loserResult = $loserAction === 'disenchant'
                ? $this->disenchantSlot($character, $trackedSlotId)
                : $this->sellSlot($character, $trackedSlotId);
            $keepBestSlots[$itemType] = $slotId;
            $batchCrafting->update(['progress' => array_merge($progress, ['keep_best_slots' => $keepBestSlots])]);

            return [
                'counts' => $this->mergeCounts(['kept_count' => 1], $loserResult['counts']),
                'details' => array_merge(['disposition' => 'keep_best_' . $loserAction . '_rest', 'kept_item' => $this->itemDetails($newSlot->item, $slotId)], $loserResult['details']),
            ];
        }

        $loserResult = $loserAction === 'disenchant'
            ? $this->disenchantSlot($character, $slotId)
            : $this->sellSlot($character, $slotId);

        return [
            'counts' => $loserResult['counts'],
            'details' => array_merge(['disposition' => 'keep_best_' . $loserAction . '_rest'], $loserResult['details']),
        ];
    }

    private function applyKeepHighest(BatchCrafting $batchCrafting, Character $character, int $slotId): array
    {
        $newSlot = $character->inventory->slots()->find($slotId);

        if (is_null($newSlot) || is_null($newSlot->item)) {
            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_highest'],
            ];
        }

        $newItem = $newSlot->item;
        $itemType = $newItem->type ?? 'weapon';
        $newLevel = (int) ($newItem->skill_level_required ?? 0);

        $progress = $batchCrafting->progress ?? [];
        $keepHighestSlots = $progress['keep_highest_slots'] ?? [];
        $trackedSlotId = $keepHighestSlots[$itemType] ?? null;

        if (is_null($trackedSlotId)) {
            $keepHighestSlots[$itemType] = $slotId;
            $batchCrafting->update(['progress' => array_merge($progress, ['keep_highest_slots' => $keepHighestSlots])]);

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($newSlot->item, $slotId)],
            ];
        }

        $trackedSlot = $character->inventory->slots()->find($trackedSlotId);

        if (is_null($trackedSlot) || is_null($trackedSlot->item)) {
            $keepHighestSlots[$itemType] = $slotId;
            $batchCrafting->update(['progress' => array_merge($progress, ['keep_highest_slots' => $keepHighestSlots])]);

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($newSlot->item, $slotId)],
            ];
        }

        $trackedLevel = (int) ($trackedSlot->item->skill_level_required ?? 0);

        if ($newLevel >= $trackedLevel) {
            $sold = $this->sellSlot($character, $trackedSlotId);
            $keepHighestSlots[$itemType] = $slotId;
            $batchCrafting->update(['progress' => array_merge($progress, ['keep_highest_slots' => $keepHighestSlots])]);

            return [
                'counts' => ['kept_count' => 1, 'sold_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($newSlot->item, $slotId), 'sold_item' => $sold['details']['sold_item'] ?? null, 'gold_gained' => $sold['details']['gold_gained'] ?? 0],
            ];
        }

        $sold = $this->sellSlot($character, $slotId);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'keep_highest', 'sold_item' => $sold['details']['sold_item'] ?? null, 'gold_gained' => $sold['details']['gold_gained'] ?? 0],
        ];
    }

    private function sellSlot(Character $character, int $slotId): array
    {
        $slot = $character->inventory->slots()->find($slotId);
        $itemDetails = is_null($slot) ? null : $this->itemDetails($slot->item, $slot->id);
        $goldBefore = $character->gold;
        $result = $this->multiInventoryActionService->sellManyItems($character, [$slotId]);

        if (isset($result['status']) && $result['status'] === 422) {
            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'sell', 'kept_item' => $itemDetails],
            ];
        }

        $goldGained = max(0, $character->refresh()->gold - $goldBefore);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'sell', 'sold_item' => $this->removedItemDetails($itemDetails, 'sold'), 'gold_gained' => $goldGained],
        ];
    }

    private function destroySlot(Character $character, int $slotId): array
    {
        $slot = $character->inventory->slots()->find($slotId);
        $itemDetails = is_null($slot) ? null : $this->itemDetails($slot->item, $slot->id);
        $this->multiInventoryActionService->destroyManyItems($character, [$slotId]);

        return [
            'counts' => ['destroyed_count' => 1],
            'details' => ['disposition' => 'destroy', 'destroyed_item' => $this->removedItemDetails($itemDetails, 'destroyed')],
        ];
    }

    private function disenchantSlot(Character $character, int $slotId): array
    {
        $slot = $character->inventory->slots()->find($slotId);
        $itemDetails = is_null($slot) ? null : $this->itemDetails($slot->item, $slot->id);
        $goldDustBefore = $character->gold_dust;
        $this->multiInventoryActionService->disenchantManyItems($character, [$slotId]);
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

    private function listSlot(Character $character, int $slotId): array
    {
        $slot = $character->inventory->slots()->find($slotId);

        if (is_null($slot)) {
            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'list'],
            ];
        }

        $price = (int) SellItemCalculator::fetchSalePriceWithAffixes($slot->item);

        if ($price < 1) {
            $price = max(1, $slot->item->cost ?? 1);
        }

        MarketBoard::create([
            'character_id' => $character->id,
            'item_id' => $slot->item_id,
            'listed_price' => $price,
        ]);

        $slot->delete();

        return [
            'counts' => ['listed_count' => 1],
            'details' => ['disposition' => 'list', 'listed_item' => $this->removedItemDetails($this->itemDetails($slot->item, $slotId), 'listed'), 'listed_price' => $price],
        ];
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

    private function listAlchemySlot(Character $character, AlchemyBagSlot $slot): array
    {
        $itemDetails = $this->itemDetails($slot->item, $slot->id);
        $price = max(1, (int) SellItemCalculator::fetchSalePriceWithAffixes($slot->item));

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

    private function inventorySlotDetails(Character $character, int $slotId): ?array
    {
        $slot = $character->inventory?->slots()->find($slotId);

        if (is_null($slot)) {
            return null;
        }

        return $this->itemDetails($slot->item, $slot->id);
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
            'crafting_type' => $item->crafting_type,
            'skill_level_required' => $item->skill_level_required,
            'affix_count' => $item->affix_count ?? 0,
            'is_unique' => (bool) ($item->is_unique ?? false),
            'holy_stacks_applied' => $item->holy_stacks_applied ?? 0,
            'is_mythic' => (bool) ($item->is_mythic ?? false),
            'is_cosmic' => (bool) ($item->is_cosmic ?? false),
            'can_view' => $canView && ! is_null($slotId),
        ];
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
