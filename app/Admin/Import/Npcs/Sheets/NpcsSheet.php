<?php

namespace App\Admin\Import\Npcs\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Flare\Models\GameMap;
use App\Flare\Models\Npc;

class NpcsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $data = array_combine($rows[0]->toArray(), $row->toArray());
                $data = $this->returnCleanData($data);

                if (!empty($data)) {
                    Npc::updateOrCreate(['real_name' => $data['real_name']], $data);
                }
            }
        }
    }

    protected function returnCleanData(array $buildingData): array {
        $cleanData = [];

        foreach ($buildingData as $key => $value) {
            if (!is_null($value)) {
                if ($key === 'game_map_id') {
                    $gameMap = GameMap::where('name', $value)->first();

                    if (is_null($gameMap)) {
                        return []; // Map does not exist: Bail.
                    }

                    $value = $gameMap->id;
                }

                $cleanData[$key] = $value;
            }
        }

        return $cleanData;
    }
}
