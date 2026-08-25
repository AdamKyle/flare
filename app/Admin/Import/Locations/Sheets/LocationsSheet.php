<?php

namespace App\Admin\Import\Locations\Sheets;

use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Game\Maps\Values\LocationType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class LocationsSheet implements ToCollection
{
    /**
     * Import location rows from the uploaded spreadsheet and persist them.
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $data = array_combine($rows[0]->toArray(), $row->toArray());
                $data = $this->returnCleanData($data);

                if (! empty($data)) {
                    Location::updateOrCreate(['name' => $data['name']], $data);
                }
            }
        }
    }

    /**
     * Build a clean location payload from a raw spreadsheet row, resolving related records.
     */
    protected function returnCleanData(array $locations): array
    {
        $cleanData = [];

        foreach ($locations as $key => $value) {
            if (! is_null($value)) {
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
                        return [];
                    }

                    $value = $item->id;
                }

                if ($key === 'required_quest_item_id') {
                    $item = Item::where('name', $value)->first();

                    if (is_null($item)) {
                        return [];
                    }

                    $value = $item->id;
                }

                $cleanData[$key] = $value;
            }
        }

        if (! isset($cleanData['can_players_enter'])) {
            $cleanData['can_players_enter'] = false;
        }

        if (! isset($cleanData['can_auto_battle'])) {
            $cleanData['can_auto_battle'] = false;
        }

        if (isset($cleanData['enemy_strength_type']) && ! isset($cleanData['type'])) {
            $cleanData['type'] = LocationType::SPECIAL->value;
        }

        unset(
            $cleanData['enemy_strength_type'],
            $cleanData['enemy_strength_increase'],
            $cleanData['delve_enemy_strength_increase'],
        );

        return $cleanData;
    }
}
