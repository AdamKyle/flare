<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Skill;
use App\Game\Skills\Services\Traits\SkillCheck;

class EnchantItemService {

    use SkillCheck;

    /**
     * @var Item $item
     */
    private $item;

    /**
     * @var int $dcIncrease
     */
    private $dcIncrease = 0;

    /**
     * Attach the affix to the item
     *
     * @param Item $item
     * @param ItemAffix $affix
     * @param Skill $enchantingSkill
     * @param bool $tooEasy
     * @return bool
     */
    public function attachAffix(Item $item, ItemAffix $affix, Skill $enchantingSkill, bool $tooEasy = false) {
        if ($tooEasy) {
            $this->enchantItem($item, $affix);
        } else {
            $dcCheck       = $this->getDCCheck($enchantingSkill, $this->dcIncrease);
            $characterRoll = $this->characterRoll($enchantingSkill);

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
     */
    public function updateSlot(InventorySlot $slot) {
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
        }
    }

    /**
     * Delete the slot.
     *
     * @param InventorySlot $slot
     * @return void
     */
    public function deleteSlot(InventorySlot $slot) {
        $slot->delete();

        if (!is_null($this->item)) {
            $this->item->delete();

            $this->item = null;
        }
    }

    /**
     * Set difficulty check increase.
     *
     * @param int $increaseBy
     * @return $this
     */
    public function setDcIncrease(int $increaseBy): EnchantItemService {
        $this->dcIncrease = $increaseBy;

        return $this;
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
        $clonedItem = $item->duplicate();

        $clonedItem->{'item_' . $affix->type . '_id'} = $affix->id;
        $clonedItem->market_sellable                  = true;
        $clonedItem->parent_id                        = $item->id;

        $clonedItem->save();

        $this->item = $this->applyHolyStacks($item, $clonedItem);
    }

    /**
     * Apply the old items holy stacks to the new item.
     *
     * @param Item $oldItem
     * @param Item $item
     * @return Item
     */
    protected function applyHolyStacks(Item $oldItem, Item $item): Item {
        dump('applying stacks');
        if ($oldItem->appliedHolyStacks()->count() > 0) {
            dump('old item has stacks');
            foreach ($oldItem->appliedHolyStacks as $stack) {
                dump('applying stack');
                $stackAttributes = $stack->getAttributes();

                $stackAttributes['item_id'] = $item->id;

                $item->appliedHolyStacks()->create($stackAttributes);
            }
        }

        dump($item->refresh()->appliedHolyStacks);

        return $item->refresh();
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
