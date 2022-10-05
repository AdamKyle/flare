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

                $skill     = GameSkill::where('name', $item['skill_name'])->first();

                if (is_null($skill) && !is_null($item['skill_name'])) {
                    continue;
                }

                $itemData = $this->returnCleanItem($item);

                // $item = Item::where('name', $itemData['name'])->first();
                $item = Item::find($itemData['id']);

                if (!is_null($item)) {
                    $item->update($itemData);

                    $this->updateChildrenElements($item->refresh());
                } else {
                    Item::create($itemData);
                }
            }
        }
    }

    protected function updateChildrenElements(Item $item) {
        foreach ($item->children as $childItem) {
            $attributes = $item->getAttributes();

            $attributes['item_suffix_id'] = $childItem->item_suffix_id;
            $attributes['item_prefix_id'] = $childItem->item_prefix_id;

            $childItem->update();
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
            if (!is_null($value)) {
                if ($key === 'can_drop') {
                    if (is_null($value)) {
                        $value = false;
                    }
                } else if ($key === 'drop_location_id') {
                    $foundLocation = Location::find($value);

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
