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
                $originalMonster = array_combine($rows[0]->toArray(), $row->toArray());

                $monster = $this->returnCleanMonster($originalMonster);

                if (is_null($monster) || !isset($monster['id'])) {
                    continue;
                }

                $foundMonster = Monster::find($monster['id']);

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
                    $questItem = Item::find($value);

                    if (is_null($questItem)) {
                        return null;
                    } else {
                        $value = $questItem->id;
                    }
                } else if ($key === 'game_map_id') {
                    $gameMap = GameMap::find($value);

                    if (is_null($gameMap)) {
                        return null;
                    } else {
                        $value = $gameMap->id;
                    }
                }

                if ($key === 'health_range_min') {
                    $cleanData['health_range'] = $monster['health_range_min'] . '-' . $monster['health_range_max'];
                }

                if ($key === 'attack_range_min') {
                    $cleanData['attack_range'] = $monster['attack_range_min'] . '-' . $monster['attack_range_max'];
                }

                if ($key === 'health_range_max') {
                    continue;
                }

                if ($key === 'attack_range_max') {
                    continue;
                }

                if ($key !== 'health_range_min' && $key !== 'attack_range_min') {
                    $cleanData[$key] = $value;
                }
            }
        }

        return $cleanData;
    }
}
