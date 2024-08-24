<?php

namespace App\Flare\Builders;

use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;

class RandomItemDropBuilder
{
    /**
     * Generates the random item for a player.
     */
    public function generateItem(int $forLevel): ?Item
    {
        $item = $this->getItem($forLevel);
        $affixes = $this->getAffixes($forLevel);

        if (count($affixes) < 1) {
            return null;
        }

        $foundItem = $this->itemExists($item, $affixes);

        if (! is_null($foundItem)) {
            return $foundItem;
        }

        return $this->createItem($item, $affixes);
    }

    /**
     * Fetches a random item with not attached affixes.
     *
     * The item cannot be a quest item, artifact item or alchemy item.
     */
    protected function getItem(int $level): Item
    {
        $query = Item::inRandomOrder()->doesntHave('itemSuffix')
            ->doesntHave('itemPrefix')
            ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->whereNull('specialty_type')
            ->where('skill_level_required', '<=', rand(1, $level));

        return $query->first();
    }

    /**
     * Fetches one or two Affixes.
     */
    protected function getAffixes(int $level): array
    {
        $affixes = [];

        $affixes[] = ItemAffix::inRandomOrder()->where('type', 'prefix')->where('skill_Level_required', '<=', rand(1, $level))->first();

        if (rand(1, 100) > 50) {
            $affix = ItemAffix::inRandomOrder()->where('type', 'suffix')->where('skill_Level_required', '<=', rand(1, $level))->first();

            if (! is_null($affix)) {
                $affixes[] = $affix;
            }
        }

        return $affixes;
    }

    /**
     * Returns a possible item that may already exist with the affixes.
     */
    protected function itemExists(Item $item, array $affixes): ?Item
    {
        $query = Item::where('id', $item->id);

        foreach ($affixes as $affix) {
            $column = 'item_'.$affix->type.'_id';
            $query->where($column, $affix->id);
        }

        return $query->first();
    }

    /**
     * Creates a new entry in the database for a new item.
     */
    protected function createItem(Item $item, array $affixes): Item
    {
        $item = $item->duplicate();

        $updates = [];

        foreach ($affixes as $affix) {
            $updates['item_'.$affix->type.'_id'] = $affix->id;
        }

        $item->update($updates);

        return $item;
    }
}
