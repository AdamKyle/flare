<?php

namespace App\Admin\Import\Monsters\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Flare\Models\Monster;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;

class MonstersSheet implements ToCollection {

    public function collection(Collection $rows) {

        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $monster = array_combine($rows[0]->toArray(), $row->toArray());

                $monster = $this->returnCleanMonster($monster);

                if (is_null($monster)) {
                    continue;
                }

                $foundMonster = Monster::where('name', $monster['name'])->first();

                if (is_null($foundMonster)) {
                    Monster::create($monster);
                } else {
                    $foundMonster->update($monster);
                }
            }
        }
    }

    protected function returnCleanMonster(array $monster) {
        $cleanData = [];

        if (is_null($monster['is_celestial_entity'])) {
            $monster['is_celestial_entity'] = false;
        } else {
            $monster['is_celestial_entity'] = true;
        }

        foreach ($monster as $key => $value) {
            if (!is_null($value)) {

                if ($key === 'quest_item_id') {
                    $questItem = Item::where('name', $value)->first();

                    if (is_null($questItem)) {
                        return null;
                    } else {
                        $value = $questItem->id;
                    }
                } else if ($key === 'game_map_id') {
                    $gameMap = GameMap::where('name', $value)->first();

                    if (is_null($gameMap)) {
                        return null;
                    } else {
                        $value = $gameMap->id;
                    }
                }

                $cleanData[$key] = $value;
            }
        }

        return $cleanData;
    }
}
