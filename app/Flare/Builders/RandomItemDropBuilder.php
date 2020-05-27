<?php

namespace App\Flare\Builders;

use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Item;
use App\Flare\Models\Character;
use App\Flare\Models\ItemAffix;

class RandomItemDropBuilder {

    private $itemAffixes; 

    public function setItemAffixes(Collection $itemAffixes): RandomItemDropBuilder {
        $this->itemAffixes = $itemAffixes;

        return $this;
    }

    public function generateItem(Character $character): Item {
        $item = Item::inRandomOrder()->with(['itemSuffix', 'itemPrefix'])->where('type', '!=', 'artifact')->where('type', '!=', 'quest')->get()->first();

        $duplicateItem = $item->replicate();
        $duplicateItem->save();

        if (!is_null($item->itemSuffix)) {
            $duplicateItem->update([
                'item_suffix_id' => $item->itemSuffix->id,
            ]);
        }

        if (!is_null($item->itemPrefix)) {
            $duplicateItem->update([
                'item_prefix_id' => $item->itemPrefix->id,
            ]);
        }

        $duplicateItem->refresh()->load(['itemSuffix', 'itemPrefix']);

        if ($this->shouldHaveItemAffix($character)) {
            $affix = $this->fetchRandomItemAffix();
            
            if (!is_null($duplicateItem->itemSuffix) || !is_null($duplicateItem->itemPrefix)) {
                $hasSameAffix = $duplicateItem->itemAffixes->where('type', '=', $affix['type'])->first();

                if (!is_null($hasSameAffix)) {
                    $duplicateItem->delete();

                    return $item;
                } else {
                    $duplicateItem = $this->attachAffix($duplicateItem, $affix);
                }
            } else {
                $duplicateItem = $this->attachAffix($duplicateItem, $affix);
            }
        } else {
            $duplicateItem->delete();

            return $item;
        }

        $duplicateItem = $this->setItemName($duplicateItem);
        $foundItems    = Item::where('name', '=', $duplicateItem->name)->get();

        if ($foundItems->count() > 1) {
            $duplicateItem->delete();

            return $item;
        }

        return $duplicateItem;
    }

    protected function attachAffix(Item $item, ItemAffix $itemAffix): Item {
        $item->update(['item_'.$itemAffix->type.'_id' => $itemAffix->id]);

        return $item->refresh();
    }

    protected function shouldHaveItemAffix(Character $character): bool {
        $lootingChance = $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;

        return (rand(1, 100) + $lootingChance) > 50;
    }

    protected function fetchRandomItemAffix(): ItemAffix {
        return $this->itemAffixes[rand(0, count($this->itemAffixes) - 1)];
    }

    private function setItemName(Item $item): Item {
        $name    = $item->name;

        if (!is_null($item->itemSuffix)) {
            $name = $name . ' *' . $item->itemSuffix->name . '*';
        }

        if (!is_null($item->itemPrefix)) {
            $name = '*' . $item->itemPrefix->name . '* ' . $name;
        }
        
        $item->name = $name;
        $item->save();

        return $item->refresh();
    }
}
