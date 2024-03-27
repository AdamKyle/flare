<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Skill;
use App\Game\Skills\Handlers\HandleUpdatingEnchantingGlobalEventGoal;
use Exception;
use Facades\App\Game\Core\Handlers\DuplicateItemHandler;

class EnchantItemService {

    /**
     * @var Item|null $item
     */
    private ?Item $item = null;

    /**
     * @var int $dcIncrease
     */
    private int $dcIncrease = 0;

    /**
     * @var SkillCheckService $skillCheckService;
     */
    private SkillCheckService $skillCheckService;

    private HandleUpdatingEnchantingGlobalEventGoal $handleUpdatingEnchantingGlobalEventGoal;

    /**
     * @param SkillCheckService $skillCheckService
     */
    public function __construct(SkillCheckService $skillCheckService, HandleUpdatingEnchantingGlobalEventGoal $handleUpdatingEnchantingGlobalEventGoal) {
        $this->skillCheckService = $skillCheckService;
        $this->handleUpdatingEnchantingGlobalEventGoal = $handleUpdatingEnchantingGlobalEventGoal;
    }

    /**
     * Attach the affix to the item
     *
     * @param Item $item
     * @param ItemAffix $affix
     * @param Skill $enchantingSkill
     * @param bool $tooEasy
     * @return bool
     */
    public function attachAffix(Item $item, ItemAffix $affix, Skill $enchantingSkill, bool $tooEasy = false): bool {
        if ($tooEasy) {
            $this->enchantItem($item, $affix);
        } else {
            $dcCheck       = $this->skillCheckService->getDCCheck($enchantingSkill, $this->dcIncrease);
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
     * @param InventorySlot $slot
     * @return void
     * @throws Exception
     */
    public function updateSlot(InventorySlot $slot): void {
        if (!is_null($this->item)) {

            if ($this->getCountOfMatchingItems() > 1) {
                $slot->update([
                    'item_id' => $this->findMatchingItemId(),
                ]);
            } else {
                $slot->update([
                    'item_id' => $this->item->id,
                ]);
            }

            $slot = $slot->refresh();

            $character = $slot->inventory->character;

            $this->handleUpdatingEnchantingGlobalEventGoal->handleUpdatingEnchantingGlobalEventGoal($character, $slot);
        }
    }

    /**
     * Delete the slot.
     *
     * @param InventorySlot $slot
     * @return void
     */
    public function deleteSlot(InventorySlot $slot): void {
        $slot->delete();

        if (!is_null($this->item)) {
            $this->item->delete();

            $this->item = null;
        }
    }

    /**
     * Get the item.
     *
     * @return Item|null
     */
    public function getItem(): ?Item {
        return $this->item;
    }

    /**
     * Enchant the item.
     *
     * @param Item $item
     * @param ItemAffix $affix
     * @return void
     */
    protected function enchantItem(Item $item, ItemAffix $affix) {
        if (!is_null($this->item)) {
            $this->cloneItem($this->item, $affix);

            return;
        }

        $this->cloneItem($item, $affix);
    }

    protected function cloneItem(Item $item, ItemAffix $affix) {
        $clonedItem = DuplicateItemHandler::duplicateItem($item);

        $clonedItem->{'item_' . $affix->type . '_id'} = $affix->id;
        $clonedItem->market_sellable                  = true;
        $clonedItem->parent_id                        = $item->id;

        $clonedItem->save();

        $this->item = $clonedItem->refresh();
    }

    /**
     * Count the matching items.
     *
     * @return int
     */
    protected function getCountOfMatchingItems(): int {
        // Holy stacks are random, so we want a matching
        // item only if this item has no stacks on it.
        if ($this->item->appliedHolyStacks()->count() === 0) {
            return Item::where('name', $this->item->name)
                ->where('item_prefix_id', $this->item->item_prefix_id)
                ->where('item_suffix_id', $this->item->item_suffix_id)
                ->count();
        }

        return 0;
    }

    /**
     * Fetch matching item id.
     *
     * @return int
     */
    protected function findMatchingItemId(): int {
        $item = $this->item;

        $this->item->delete();
        $this->item = null;

        return Item::where('name', $item->name)
                   ->where('item_prefix_id', $item->item_prefix_id)
                   ->where('item_suffix_id', $item->item_suffix_id)
                   ->first()
                   ->id;
    }
}
