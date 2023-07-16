<?php

namespace App\Admin\Import\Locations\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Flare\Models\Item;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;

class LocationsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $data = array_combine($rows[0]->toArray(), $row->toArray());
                $data = $this->returnCleanData($data);

                if (!empty($data)) {
                    Location::updateOrCreate(['name' => $data['name']], $data);
                }
            }
        }
    }

    protected function returnCleanData(array $locations): array {
        $cleanData = [];

        foreach ($locations as $key => $value) {
            if (!is_null($value)) {
                if ($key === 'game_map_id') {
                    $gameMap = GameMap::where('name', $value)->first();

                    if (is_null($gameMap)) {
                        return [];
                    }

                    $value = $gameMap->id;
                }

                if ($key === 'quest_reward_item_id') {
                    $item = Item::where('name', $value)->first();

                    if (is_null($item)) {
                        return[];
                    }

                    $value = $item->id;
                }

                if ($key === 'required_quest_item_id') {
                    $item = Item::where('name', $value)->first();

                    if (is_null($item)) {
                        return[];
                    }

                    $value = $item->id;
                }

                $cleanData[$key] = $value;
            }
        }

        return $cleanData;
    }
}
