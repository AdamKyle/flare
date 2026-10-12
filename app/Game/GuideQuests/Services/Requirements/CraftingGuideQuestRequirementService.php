<?php

namespace App\Game\GuideQuests\Services\Requirements;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Builder;

class CraftingGuideQuestRequirementService
{
    /**
     * Return completed Batch Crafting requirement keys.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return array
     */
    public function completedRequirements(Character $character, GuideQuest $quest): array
    {
        return array_values(array_filter([
            $this->batchCraftingExperience($character, $quest),
            $this->batchCraftedItems($character, $quest),
        ]));
    }

    /**
     * Determine whether every configured crafted Item requirement is complete.
     *
     * @param Character $character
     * @param array $requiredBatchCraftedItems
     * @return bool
     */
    public function hasRequiredBatchCraftedItems(Character $character, array $requiredBatchCraftedItems): bool
    {
        if (empty($requiredBatchCraftedItems)) {
            return true;
        }

        return collect($this->batchCraftedItemRequirements($character, $requiredBatchCraftedItems))
            ->every(fn (array $requirement): bool => $requirement['is_complete']);
    }

    /**
     * Return factual progress for configured crafted Item requirements.
     *
     * @param Character $character
     * @param array $requiredBatchCraftedItems
     * @return array
     */
    public function batchCraftedItemRequirements(Character $character, array $requiredBatchCraftedItems): array
    {
        return collect($requiredBatchCraftedItems)
            ->values()
            ->map(function (array $requiredItem, int $index) use ($character): array {
                $itemId = $requiredItem['item_id'] ?? null;
                $requiredAmount = $requiredItem['amount'] ?? null;
                $source = $requiredItem['source'] ?? 'inventory';
                $mustBeEnchanted = $requiredItem['must_be_enchanted'] ?? false;

                if ($source === 'alchemy_bag') {
                    $mustBeEnchanted = false;
                }

                $validItem = is_int($itemId);
                $validAmount = is_int($requiredAmount) && $requiredAmount >= 1;
                $validEnchanted = is_bool($mustBeEnchanted);
                $currentAmount = 0;

                if ($validItem && $validAmount && $validEnchanted) {
                    $currentAmount = $source === 'alchemy_bag'
                        ? AlchemyBagSlot::where('character_id', $character->id)
                            ->where('item_id', $itemId)
                            ->sum('amount')
                        : $this->matchingBatchCraftedItemSlotsQuery($character, $itemId, $mustBeEnchanted)->count();
                }

                return [
                    'requirement_index' => $index,
                    'source' => $source,
                    'item_id' => $itemId,
                    'required_amount' => $requiredAmount,
                    'current_amount' => $currentAmount,
                    'must_be_enchanted' => $validEnchanted ? $mustBeEnchanted : false,
                    'is_complete' => $validItem && $validAmount && $validEnchanted && $currentAmount >= $requiredAmount,
                ];
            })
            ->all();
    }

    /**
     * Determine whether an Alchemy Bag contains the configured Item amount.
     *
     * @param Character $character
     * @param array $requiredBatchCraftedItem
     * @return bool
     */
    public function hasRequiredAlchemyBagItemAmount(Character $character, array $requiredBatchCraftedItem): bool
    {
        $itemId = $requiredBatchCraftedItem['item_id'] ?? null;
        $requiredAmount = $requiredBatchCraftedItem['amount'] ?? null;

        if (! is_int($itemId) || ! is_int($requiredAmount) || $requiredAmount < 1) {
            return false;
        }

        return AlchemyBagSlot::where('character_id', $character->id)
            ->where('item_id', $itemId)
            ->sum('amount') >= $requiredAmount;
    }

    /**
     * Return matching normal Inventory Slot identifiers for a crafted Item requirement.
     *
     * @param Character $character
     * @param array $requiredBatchCraftedItem
     * @return array
     */
    public function matchingBatchCraftedItemSlotIds(Character $character, array $requiredBatchCraftedItem): array
    {
        $itemId = $requiredBatchCraftedItem['item_id'] ?? null;
        $requiredAmount = $requiredBatchCraftedItem['amount'] ?? null;
        $mustBeEnchanted = $requiredBatchCraftedItem['must_be_enchanted'] ?? false;

        if (! is_int($itemId) || ! is_int($requiredAmount) || $requiredAmount < 1 || ! is_bool($mustBeEnchanted)) {
            return [];
        }

        return $this->matchingBatchCraftedItemSlotsQuery($character, $itemId, $mustBeEnchanted)
            ->limit($requiredAmount)
            ->pluck('id')
            ->all();
    }

    /**
     * Build the normal Inventory query for a crafted Item requirement.
     *
     * @param Character $character
     * @param int $itemId
     * @param bool $mustBeEnchanted
     * @return Builder
     */
    private function matchingBatchCraftedItemSlotsQuery(Character $character, int $itemId, bool $mustBeEnchanted): Builder
    {
        $item = Item::find($itemId);

        return InventorySlot::query()
            ->where('inventory_id', $character->inventory->id)
            ->where('equipped', false)
            ->whereHas('item', function (Builder $query) use ($item, $mustBeEnchanted): void {
                if (is_null($item)) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where('name', $item->name)
                    ->where('type', $item->type)
                    ->when(
                        $mustBeEnchanted,
                        fn (Builder $itemQuery) => $itemQuery->whereNotNull('item_prefix_id')->whereNotNull('item_suffix_id'),
                        fn (Builder $itemQuery) => $itemQuery->whereNull('item_prefix_id')->whereNull('item_suffix_id'),
                    );
            });
    }

    /**
     * Return the completed Batch Crafting experience requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function batchCraftingExperience(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_batch_crafting_type) || is_null($quest->required_batch_crafting_hours)) {
            return null;
        }

        $completed = BatchCrafting::where('character_id', $character->id)
            ->where('batch_type', $quest->required_batch_crafting_type)
            ->whereNotNull('started_at')
            ->get()
            ->contains(fn (BatchCrafting $batchCrafting): bool => $this->batchCraftingWasRunForExperience(
                $batchCrafting,
                $quest->required_batch_crafting_hours,
            ));

        return $completed ? 'required_batch_crafting_hours' : null;
    }

    /**
     * Return the completed crafted Items requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function batchCraftedItems(Character $character, GuideQuest $quest): ?string
    {
        $requirements = $quest->required_batch_crafted_items ?? [];

        return ! empty($requirements) && $this->hasRequiredBatchCraftedItems($character, $requirements)
            ? 'required_batch_crafted_items'
            : null;
    }

    /**
     * Determine whether one Batch Crafting run satisfies the experience duration.
     *
     * @param BatchCrafting $batchCrafting
     * @param int $requiredHours
     * @return bool
     */
    private function batchCraftingWasRunForExperience(BatchCrafting $batchCrafting, int $requiredHours): bool
    {
        if (is_null($batchCrafting->started_at)) {
            return false;
        }

        $modeKey = match ($batchCrafting->batch_type) {
            'alchemy' => 'alchemy_mode',
            'trinketry' => 'trinketry_mode',
            default => 'craft_mode',
        };

        if (($batchCrafting->progress[$modeKey] ?? null) !== 'experience') {
            return false;
        }

        $endedAt = $batchCrafting->cancelled_at ?? $batchCrafting->completed_at ?? now();

        return $batchCrafting->started_at->diffInMinutes($endedAt) >= $requiredHours * 60;
    }
}
