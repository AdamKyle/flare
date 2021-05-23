<?php

namespace App\Admin\Import\Items\Sheets;

use App\Flare\Models\Item;
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

        foreach ($item as $key => $value) {
            if (!is_null($value) || ($key === 'item_suffix_id' || $key === 'item_prefix_id')) {
                $cleanData[$key] = $value;
            }
        }

        return $cleanData;
    }
}
