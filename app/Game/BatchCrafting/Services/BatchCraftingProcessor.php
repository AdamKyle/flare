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
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
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
    private array $deferredSellSlotIds = [];

    private ?Character $deferredSellCharacter = null;

    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly AlchemyService $alchemyService,
        private readonly TrinketCraftingService $trinketCraftingService,
        private readonly EnchantingService $enchantingService,
        private readonly HolyItemService $holyItemService,
        private readonly MultiInventoryActionService $multiInventoryActionService,
        private readonly BatchCraftingSetService $batchCraftingSetService,
    ) {}

    public function processOneTick(BatchCrafting $batchCrafting, Character $character): array
    {
        $this->deferredSellSlotIds = [];
        $this->deferredSellCharacter = $character;

        $type = BatchCraftingType::from($batchCrafting->batch_type);
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);

        $result = match ($type) {
            BatchCraftingType::CRAFT => $this->processCraft($batchCrafting, $character, $disposition),
            BatchCraftingType::CRAFT_AND_ENCHANT => $this->processCraftAndEnchant($batchCrafting, $character, $disposition),
            BatchCraftingType::ENCHANT => ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT],
            BatchCraftingType::ALCHEMY => $this->processAlchemy($batchCrafting, $character, $disposition),
            BatchCraftingType::HOLY_OILS => $this->processHolyOils($batchCrafting, $character),
            BatchCraftingType::TRINKETRY => $this->processTrinketry($batchCrafting, $character, $disposition),
        };

        $this->flushDeferredSells();

        return $result;
    }

    private function flushDeferredSells(): void
    {
        if (! empty($this->deferredSellSlotIds) && ! is_null($this->deferredSellCharacter)) {
            $this->multiInventoryActionService->sellManyItems($this->deferredSellCharacter, $this->deferredSellSlotIds);
        }

        $this->deferredSellSlotIds = [];
        $this->deferredSellCharacter = null;
    }

    private function processCraft(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $craftMode = $progress['craft_mode'] ?? 'experience';

        if ($craftMode === 'experience') {
            if ($this->shouldMoveKeptOutputToBatchSet($disposition) && ! $this->batchCraftingSetService->canAccept($character, BatchCraftingService::SETS_PER_RECURRING_TICK * 23)) {
                return ['end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL];
            }

            return $this->processRepeatedActions(BatchCraftingService::SETS_PER_RECURRING_TICK * 23, function () use ($batchCrafting, $character, $disposition, $progress) {
                return $this->processCraftExperience($batchCrafting->refresh(), $character->refresh(), $disposition, $batchCrafting->refresh()->progress ?? $progress, true);
            });
        }

        if ($craftMode === 'specific_item') {
            return $this->processCraftSpecificItem($batchCrafting, $character, $disposition, $progress);
        }

        return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
    }

    private function processCraftAndEnchant(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $craftMode = $progress['craft_mode'] ?? 'experience';

        if ($craftMode === 'experience') {
            if ($this->shouldMoveKeptOutputToBatchSet($disposition) && ! $this->batchCraftingSetService->canAccept($character, BatchCraftingService::SETS_PER_RECURRING_TICK * 23)) {
                return ['end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL];
            }

            return $this->processRepeatedActions(BatchCraftingService::SETS_PER_RECURRING_TICK * 23, function () use ($batchCrafting, $character, $disposition, $progress) {
                return $this->processCraftAndEnchantExperience($batchCrafting->refresh(), $character->refresh(), $disposition, $batchCrafting->refresh()->progress ?? $progress);
            });
        }

        if ($craftMode === 'specific_item') {
            return $this->processCraftAndEnchantSpecificItem($batchCrafting, $character, $disposition, $progress);
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

                return $result;
            }
        } else {
            return $this->processRepeatedActions(BatchCraftingService::ITEMS_PER_RECURRING_TICK, function () use ($batchCrafting, $character, $disposition) {
                return $this->processAlchemySingle($batchCrafting->refresh(), $character->refresh(), $disposition);
            });
        }

        return $this->processAlchemySingle($batchCrafting, $character, $disposition);
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

        if (! $character->canAddToAlchemyBag(1)) {
            return ['end_reason' => BatchCraftingEndReason::NO_INVENTORY_SPACE, 'actions' => [[
                'action' => 'alchemy',
                'status' => 'failed',
                'failure' => 'Alchemy bag does not have enough space.',
            ]]];
        }

        if ($alchemyMode === 'amount') {
            $alchemyItemId = (int) ($progress['alchemy_item_id'] ?? 0);
            $item = $items->first(fn ($alchemyItem) => (int) $alchemyItem->id === $alchemyItemId);

            if (is_null($item)) {
                return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
            }
        } else {
            $item = $items->sortByDesc('skill_level_required')->first();
        }
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
        $progress = $batchCrafting->progress ?? [];
        $requestedApplications = max(1, count($batchCrafting->selected_items ?? []) * count($batchCrafting->selected_oils ?? []));
        $progress['holy_oil_requested_applications'] = $progress['holy_oil_requested_applications'] ?? $requestedApplications;
        $batchCrafting->update(['progress' => $progress]);

        $result = $this->processRepeatedActions($requestedApplications, function () use ($batchCrafting, $character) {
            return $this->processHolyOilSingle($batchCrafting->refresh(), $character->refresh());
        });

        $updatedProgress = $batchCrafting->refresh()->progress ?? [];

        if (($updatedProgress['all_oils_applied'] ?? false) === true && ! isset($result['end_reason'])) {
            $result['end_reason'] = BatchCraftingEndReason::ALL_OILS_APPLIED;
        }

        return $result;
    }

    private function processHolyOilSingle(BatchCrafting $batchCrafting, Character $character): array
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
        $progress['holy_oil_completed_applications'] = ((int) ($progress['holy_oil_completed_applications'] ?? 0)) + 1;

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
        if ($this->shouldMoveKeptOutputToBatchSet($disposition) && ! $this->batchCraftingSetService->canAccept($character, BatchCraftingService::ITEMS_PER_RECURRING_TICK)) {
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

        $result = ['counts' => $counts, 'actions' => $actions];
        $this->moveKeptInventoryOutputToBatchSet($character->refresh(), $result);

        return $result;
    }

    private function processCraftExperience(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress, bool $moveKeptOutputToBatchSet = false): array
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

        if ($craftingSkills->isNotEmpty() && $craftingSkills->every(fn ($skill) => $skill->level >= $skill->max_level)) {
            return ['end_reason' => BatchCraftingEndReason::SKILL_MAXED];
        }

        $queue = $progress['craft_experience_queue'] ?? null;

        if (is_null($queue)) {
            $targets = array_merge(
                array_map(fn (string $weaponType) => ['type' => $weaponType, 'crafting_type' => $weaponType], ItemType::validWeapons()),
                array_map(fn (string $armourType) => ['type' => $armourType, 'crafting_type' => 'armour'], ArmourType::allTypes()),
                [
                    ['type' => ItemType::RING->value, 'crafting_type' => 'ring'],
                    ['type' => ItemType::RING->value, 'crafting_type' => 'ring'],
                    ['type' => ItemType::SPELL_DAMAGE->value, 'crafting_type' => 'spell'],
                    ['type' => ItemType::SPELL_HEALING->value, 'crafting_type' => 'spell'],
                ],
            );
            $queue = $targets;
            $hasEligibleTarget = false;

            foreach ($targets as $targetToCheck) {
                try {
                    $hasEligibleTarget = $this->specificCraftableItems($character, $targetToCheck['crafting_type'])
                        ->first(fn ($craftableItem) => $craftableItem->type === $targetToCheck['type']) !== null;
                } catch (\Throwable) {
                    $hasEligibleTarget = false;
                }

                if ($hasEligibleTarget) {
                    break;
                }
            }

            $progress['craft_experience_queue'] = $queue;
            $progress['craft_experience_index'] = 0;
            $progress['craft_experience_has_targets'] = $hasEligibleTarget;
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

        $target = $queue[$index];
        $craftingType = $target['crafting_type'];

        try {
            $craftableItems = $this->specificCraftableItems($character, $craftingType);
        } catch (\Throwable) {
            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'craft',
                'status' => 'skipped',
                'failure' => 'No craftable item found for type: ' . $target['type'],
            ]]];
        }

        $craftableItem = $craftableItems
            ->filter(fn ($craftableItem) => $craftableItem->type === $target['type'])
            ->sortByDesc('skill_level_required')
            ->first();
        $item = is_null($craftableItem) ? null : Item::find($craftableItem->id);
        $progress['craft_experience_index'] = $index + 1;
        $batchCrafting->update(['progress' => $progress]);

        if (is_null($item)) {
            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'craft',
                'status' => 'skipped',
                'failure' => 'No craftable item found for type: ' . $target['type'],
            ]]];
        }

        $result = $this->craftItem($batchCrafting, $character, $disposition, $item, $craftingType);

        if ($moveKeptOutputToBatchSet) {
            $this->moveKeptInventoryOutputToBatchSet($character->refresh(), $result);
        }

        return $result;
    }

    private function processCraftAndEnchantExperience(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $result = $this->processCraftExperience($batchCrafting, $character, BatchCraftingDisposition::KEEP, $progress, false);
        $slotId = $result['actions'][0]['kept_item']['slot_id'] ?? $result['actions'][0]['crafted_item']['slot_id'] ?? null;

        if (! is_null($slotId)) {
            $slot = InventorySlot::find($slotId);

            if (! is_null($slot) && $this->tryEnchantSlot($character->refresh(), $slot, $progress['enchant_affix_ids'] ?? null)) {
                $dispositionResult = $this->applyDisposition($batchCrafting, $character->refresh(), $slotId, $disposition);
                $result['counts'] = $this->mergeCounts($result['counts'] ?? [], ['enchanted_count' => 1]);
                $result['counts'] = $this->mergeCounts($result['counts'], $dispositionResult['counts']);
                $result['actions'][0] = array_merge($result['actions'][0], ['enchanted_item' => $this->itemDetails($slot->refresh()->item, $slotId)], $dispositionResult['details']);
                $this->moveKeptInventoryOutputToBatchSet($character->refresh(), $result);
            } elseif (! is_null($slot)) {
                $result['counts'] = $this->mergeCounts($result['counts'] ?? [], ['failed_count' => 1]);
                $result['actions'][0]['failure'] = 'Enchanting service did not apply an enchantment.';
                $dispositionResult = $this->applyDisposition($batchCrafting, $character->refresh(), $slotId, $disposition);
                $result['counts'] = $this->mergeCounts($result['counts'], $dispositionResult['counts']);
                $result['actions'][0] = array_merge($result['actions'][0], $dispositionResult['details']);
                $this->moveKeptInventoryOutputToBatchSet($character->refresh(), $result);
            }
        }

        return $result;
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

    private function specificCraftableItems(Character $character, string $craftingType)
    {
        return $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => $craftingType,
        ], false);
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

    private function shouldMoveKeptOutputToBatchSet(BatchCraftingDisposition $disposition): bool
    {
        return in_array($disposition, [
            BatchCraftingDisposition::KEEP,
            BatchCraftingDisposition::KEEP_HIGHEST,
            BatchCraftingDisposition::KEEP_BEST_SELL_REST,
            BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST,
        ], true);
    }

    private function moveKeptInventoryOutputToBatchSet(Character $character, array &$result): void
    {
        foreach ($result['actions'] ?? [] as $action) {
            $slotId = $action['kept_item']['slot_id'] ?? null;

            if (is_null($slotId)) {
                continue;
            }

            $slot = InventorySlot::find($slotId);

            if (! is_null($slot)) {
                $moveResult = $this->batchCraftingSetService->moveInventorySlotIntoBatchCraftingSet($character, $slot);

                if ($moveResult['success']) {
                    continue;
                }

                if ($moveResult['reason'] === 'set_full') {
                    $result['end_reason'] = BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL;

                    return;
                }

                $result['counts'] = $this->mergeCounts($result['counts'] ?? [], ['failed_count' => 1]);
                $result['actions'][] = [
                    'action' => 'keep',
                    'status' => 'failed',
                    'failure' => 'Could not move item into Crafted Items Set: ' . ($moveResult['reason'] ?? 'unknown'),
                ];
                $result['end_reason'] = BatchCraftingEndReason::FAILED;

                return;
            }
        }
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
        $goldEstimate = is_null($slot) ? 0 : max(0, (int) SellItemCalculator::fetchSalePriceWithAffixes($slot->item));

        $this->deferredSellSlotIds[] = $slotId;

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'sell', 'sold_item' => $this->removedItemDetails($itemDetails, 'sold'), 'gold_gained' => $goldEstimate],
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
