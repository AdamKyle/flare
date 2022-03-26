<?php

namespace App\Admin\Import\Items\Sheets;

use App\Flare\Models\Item;
use App\Flare\Models\Location;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Flare\Models\GameSkill;
use App\Flare\Models\ItemAffix;

class ItemsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $item = array_combine($rows[0]->toArray(), $row->toArray());

                $affixes   = ItemAffix::whereIn('name', [$item['item_suffix_id'], $item['item_prefix_id']])->get();
                $skill     = GameSkill::where('name', $item['skill_name'])->first();

                if ($affixes->isEmpty() && (!is_null($item['item_suffix_id']) || !is_null($item['item_prefix_id']))) {
                    continue;
                }

                if (is_null($skill) && !is_null($item['skill_name'])) {
                    continue;
                }

                $itemData = $this->returnCleanItem($item);

                if (!isset($itemData['name'])) {
                    continue;
                }

                $item = Item::where('name', $itemData['name'])
                    ->where('item_suffix_id', $itemData['item_suffix_id'])
                    ->where('item_prefix_id', $itemData['item_prefix_id'])
                    ->first();

                if (!is_null($item)) {
                    $item->update($itemData);
                } else {
                    Item::create($itemData);
                }
            }
        }
    }

    protected function returnCleanItem(array $item) {
        $cleanData = [];

        if (!isset($item['can_drop'])) {
            $item['can_drop'] = false;
        }

        if (!isset($item['market_sellable'])) {
            $item['market_sellable'] = false;
        }

        if (!isset($item['usable'])) {
            $item['usable'] = false;
        }

        if (!isset($item['damages_kingdoms'])) {
            $item['damages_kingdoms'] = false;
        }

        if (!isset($item['stat_increase'])) {
            $item['stat_increase'] = false;
        }

        if (!isset($item['can_craft'])) {
            $item['can_craft'] = false;
        }

        if (!isset($item['craft_only'])) {
            $item['craft_only'] = false;
        }

        if (!isset($item['can_resurrect'])) {
            $item['can_resurrect'] = false;
        }

        if (!isset($item['ignores_caps'])) {
            $item['ignores_caps'] = false;
        }

        if (!isset($item['can_use_on_other_items'])) {
            $item['can_use_on_other_items'] = false;
            $item['holy_level']             = null;
        }

        foreach ($item as $key => $value) {
            if (!is_null($value) || ($key === 'item_suffix_id' || $key === 'item_prefix_id')) {

                if ($key === 'item_suffix_id') {
                    $foundSuffix = ItemAffix::where('name', $value)->first();

                    if (is_null($foundSuffix)) {
                        $value = null;
                    } else {
                        $value = $foundSuffix->id;
                    }

                } else if ($key === 'item_prefix_id') {
                    $foundPrefix = ItemAffix::where('name', $value)->first();

                    if (is_null($foundPrefix)) {
                        $value = null;
                    } else {
                        $value = $foundPrefix->id;
                    }
                } else if ($key === 'can_drop') {
                    if (is_null($value)) {
                        $value = false;
                    }
                } else if ($key === 'drop_location_id') {
                    $foundLocation = Location::where('name', $value)->first();

                    if (is_null($foundLocation)) {
                        $value = null;
                    } else {
                        $value = $foundLocation->id;
                    }
                }

                $cleanData[$key] = $value;
            }
        }

        return $cleanData;
    }
}
