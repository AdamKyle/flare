<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Skill;
use App\Game\Core\Items\Values\RandomAffixTier;
use App\Game\Skills\Handlers\HandleUpdatingEnchantingGlobalEventGoal;
use Exception;
use Facades\App\Game\Core\Handlers\DuplicateItemHandler;

class EnchantItemService
{
    private ?Item $item = null;

    private int $dcIncrease = 0;

    private SkillCheckService $skillCheckService;

    private HandleUpdatingEnchantingGlobalEventGoal $handleUpdatingEnchantingGlobalEventGoal;

    public function __construct(SkillCheckService $skillCheckService, HandleUpdatingEnchantingGlobalEventGoal $handleUpdatingEnchantingGlobalEventGoal)
    {
        $this->skillCheckService = $skillCheckService;
        $this->handleUpdatingEnchantingGlobalEventGoal = $handleUpdatingEnchantingGlobalEventGoal;
    }

    /**
     * Attach the affix to the item
     */
    public function attachAffix(Item $item, ItemAffix $affix, Skill $enchantingSkill, bool $tooEasy = false): bool
    {
        if ($tooEasy) {
            $this->enchantItem($item, $affix);
        } else {
            $dcCheck = $this->skillCheckService->getDCCheck($enchantingSkill, $this->dcIncrease);
            $characterRoll = $this->skillCheckService->characterRoll($enchantingSkill);

            if ($dcCheck > $characterRoll) {
                return false;
            } else {
                $this->enchantItem($item, $affix);
            }
        }

        return true;
    }

    /**
     * Update the slot.
     *
     * @throws Exception
     */
    public function updateSlot(InventorySlot|GlobalEventCraftingInventorySlot $slot, bool $enchantForEvent): void
    {
        if (! is_null($this->item)) {

            if ($this->item->appliedHolyStacks->isEmpty() && $this->item->sockets->isEmpty()) {
                if ($this->getCountOfMatchingItems() > 1) {
                    $temporaryClone = $this->item;
                    $matchingItemId = $this->findMatchingItemId($temporaryClone);

                    $slot->update([
                        'item_id' => $matchingItemId,
                    ]);

                    $slot = $slot->refresh();

                    $this->deleteOrphanedClone($temporaryClone, $matchingItemId);

                    $this->item = null;
                } else {
                    $slot->update([
                        'item_id' => $this->item->id,
                    ]);

                    $slot = $slot->refresh();
                }
            } else {
                $slot->update([
                    'item_id' => $this->item->id,
                ]);

                $slot = $slot->refresh();
            }

            if ($enchantForEvent) {
                $character = $slot->inventory->character;

                $this->handleUpdatingEnchantingGlobalEventGoal->handleUpdatingEnchantingGlobalEventGoal($character, $slot);
            }
        }
    }

    /**
     * Delete the slot.
     *
     * Never deletes the item if it is still referenced by another
     * inventory slot, set slot, market listing, or market history entry.
     */
    public function deleteSlot(InventorySlot|GlobalEventCraftingInventorySlot $slot): void
    {
        $slot->delete();

        if (! is_null($this->item)) {
            $item = $this->item;

            $this->item = null;

            if ($this->itemHasNoRemainingReferences($item)) {
                $item->delete();
            }
        }
    }

    /**
     * Get the item.
     */
    public function getItem(): ?Item
    {
        return $this->item;
    }

    /**
     * Finalize the pending clone for a direct item write (no slot involved).
     *
     * Mirrors the dedupe branch of updateSlot(): if an existing item already matches
     * the clone's name/prefix/suffix, the clone is discarded and the existing item's
     * id is returned instead so batch crafting doesn't proliferate near-duplicate rows.
     */
    public function finalizeBatchItem(): ?Item
    {
        if (is_null($this->item)) {
            return null;
        }

        if ($this->item->appliedHolyStacks->isEmpty() && $this->item->sockets->isEmpty() && $this->getCountOfMatchingItems() > 1) {
            $temporaryClone = $this->item;
            $matchingItemId = $this->findMatchingItemId($temporaryClone);
            $finalItem = Item::find($matchingItemId);

            $this->deleteOrphanedClone($temporaryClone, $matchingItemId);

            $this->item = null;

            return $finalItem;
        }

        $finalItem = $this->item;
        $this->item = null;

        return $finalItem;
    }

    /**
     * Discard a pending clone that will never be referenced, for example when a
     * direct item enchant attempt fails before anything points at the clone.
     */
    public function discardPendingItem(): void
    {
        if (is_null($this->item)) {
            return;
        }

        $item = $this->item;

        $this->item = null;

        if ($this->itemHasNoRemainingReferences($item)) {
            $item->delete();
        }
    }

    /**
     * Enchant the item.
     *
     * @return void
     */
    private function enchantItem(Item $item, ItemAffix $affix)
    {
        if (! is_null($this->item)) {
            $this->cloneItem($this->item, $affix);

            return;
        }

        $this->cloneItem($item, $affix);
    }

    private function cloneItem(Item $item, ItemAffix $affix)
    {
        $clonedItem = DuplicateItemHandler::duplicateItem($item);

        $clonedItem->{'item_'.$affix->type.'_id'} = $affix->id;
        $clonedItem->market_sellable = true;
        $clonedItem->parent_id = $item->id;
        $clonedItem->is_mythic = false;
        $clonedItem->is_cosmic = false;

        if ($affix->type === 'suffix') {

            if (! is_null($clonedItem->itemSuffix)) {
                if ($clonedItem->itemSuffix->cost === RandomAffixTier::MYTHIC->value) {
                    $clonedItem->item_suffix_id = null;
                }

                if ($clonedItem->itemSuffix->cost === RandomAffixTier::COSMIC->value) {
                    $clonedItem->item_suffix_id = null;
                }
            }
        }

        if ($affix->type === 'prefix') {

            if (! is_null($clonedItem->itemPrefix)) {
                if ($clonedItem->itemPrefix->cost === RandomAffixTier::MYTHIC->value) {
                    $clonedItem->item_prefix_id = null;
                }

                if ($clonedItem->itemPrefix->cost === RandomAffixTier::COSMIC->value) {
                    $clonedItem->item_prefix_id = null;
                }
            }
        }

        $clonedItem->save();

        $this->item = $clonedItem->refresh();
    }

    /**
     * Count the matching items.
     */
    private function getCountOfMatchingItems(): int
    {
        return Item::where('name', $this->item->name)
            ->where('item_prefix_id', $this->item->item_prefix_id)
            ->where('item_suffix_id', $this->item->item_suffix_id)
            ->whereDoesntHave('appliedHolyStacks')
            ->whereDoesntHave('sockets')
            ->count();
    }

    /**
     * Fetch the id of an existing item matching the temporary clone.
     *
     * Does not delete the temporary clone. The slot must be switched to the
     * returned id first; the caller deletes the clone afterwards if it is safe to do so.
     */
    private function findMatchingItemId(Item $temporaryClone): int
    {
        $matchingItem = Item::where('name', $temporaryClone->name)
            ->where('item_prefix_id', $temporaryClone->item_prefix_id)
            ->where('item_suffix_id', $temporaryClone->item_suffix_id)
            ->where('id', '!=', $temporaryClone->id)
            ->whereDoesntHave('appliedHolyStacks')
            ->whereDoesntHave('sockets')
            ->first();

        return is_null($matchingItem) ? $temporaryClone->id : $matchingItem->id;
    }

    /**
     * Delete the temporary clone once the slot has moved off of it, but only
     * when nothing else references it.
     */
    private function deleteOrphanedClone(Item $temporaryClone, int $matchingItemId): void
    {
        if ($temporaryClone->id === $matchingItemId) {
            return;
        }

        $temporaryClone = $temporaryClone->fresh();

        if (! is_null($temporaryClone) && $this->itemHasNoRemainingReferences($temporaryClone)) {
            $temporaryClone->delete();
        }
    }

    /**
     * Whether the item is safe to delete: not referenced by any inventory
     * slot, set slot, market listing, or market history entry.
     */
    private function itemHasNoRemainingReferences(Item $item): bool
    {
        return $item->inventorySlots()->doesntExist()
            && $item->inventorySetSlots()->doesntExist()
            && $item->marketListings()->doesntExist()
            && $item->marketHistory()->doesntExist();
    }
}
