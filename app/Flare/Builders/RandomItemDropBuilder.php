<?php

namespace App\Flare\Builders;

use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;

class RandomItemDropBuilder {

    /**
     * @var Collection $itemAffixes
     */
    private $itemAffixes;

    /**
     * Set the item affixes
     *
     * @param Colletion $itemAffixes
     * @return RandomItemDropBuilder
     */
    public function setItemAffixes(Collection $itemAffixes): RandomItemDropBuilder {
        $this->itemAffixes = $itemAffixes;

        return $this;
    }

    /**
     * Generate an item.
     *
     * This will generate a random item.
     *
     * We start by fetching a random item with prefixes and suffixes., we then duplicate the item and fetch a random affix.
     * From that we check if the affix is the same on the item - if it is, atach it, if not, check if its the same, if it is, delete the
     * duplicate and return the item in question - or attach the new affix and pass that back.
     *
     * @return Item
     */
    public function generateItem(): Item {
        $item          = Item::inRandomOrder()->with(['itemSuffix', 'itemPrefix'])
                                              ->whereNotIn('type', ['artifact', 'quest', 'alchemy'])
                                              ->where('can_drop', true)
                                              ->get()
                                              ->first();
        $duplicateItem = $this->duplicateItem($item);
        $affix         = $this->fetchRandomItemAffix();

        if (!is_null($duplicateItem->itemSuffix) || !is_null($duplicateItem->itemPrefix)) {
            $duplicateItem = $this->attachAffixOrDelete($duplicateItem, $affix);
        } else {
            $duplicateItem = $this->attachAffixOrDelete($duplicateItem, $affix);
        }

        if (is_null($duplicateItem)) {
            return $item;
        }

        $duplicateItem->update([
            'market_sellable' => true,
        ]);

        return $duplicateItem->refresh();
    }

    protected function attachAffixOrDelete(Item $duplicateItem, ItemAffix $affix) {
        if ($this->hasSameAffix($duplicateItem, $affix)) {
            $duplicateItem->delete();
        } else {
            return $this->attachAffix($duplicateItem, $affix);
        }

        return null;
    }

    protected function duplicateItem(Item $item): Item {
        $duplicateItem = $item->duplicate();

        return $duplicateItem->refresh()->load(['itemSuffix', 'itemPrefix']);
    }

    protected function hasSameAffix(Item $duplicateItem, ItemAffix $affix): bool {
        $item = Item::where('item_' . $affix->type . '_id', $affix->id)->where('name', $duplicateItem->name)->first();

        return !is_null($item);
    }

    protected function attachAffix(Item $item, ItemAffix $itemAffix): Item {
        $item->update(['item_'.$itemAffix->type.'_id' => $itemAffix->id]);

        if ($itemAffix->type === 'suffix') {
            if (!is_null($item->itemPrefix)) {
                $affixes = array_values($this->itemAffixes->where('type', 'prefix')->all());

                $item->update(['item_prefix_id' => $affixes[rand(0, count($affixes) - 1)]->id]);
            }
        }

        if ($itemAffix->type === 'prefix') {
            if (!is_null($item->itemSuffix)) {
                $affixes = array_values($this->itemAffixes->where('type', 'suffix')->all());

                $item->update(['item_suffix_id' => $affixes[rand(0, count($affixes) - 1)]->id]);
            }
        }

        return $item->refresh();
    }

    protected function fetchRandomItemAffix() {
        $index = count($this->itemAffixes) - 1;

        return $this->itemAffixes[rand(0, $index)];
    }
}
