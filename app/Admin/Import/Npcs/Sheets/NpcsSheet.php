<?php

namespace App\Admin\Import\Npcs\Sheets;

use App\Flare\Models\GameMap;
use App\Flare\Models\Npc;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class NpcsSheet implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $rowData = array_combine($rows[0]->toArray(), $row->toArray());
                $data = $this->returnCleanData($rowData);

                if (! empty($data)) {
                    Npc::updateOrCreate(['id' => $data['id']], $data);
                }
            }
        }
    }

    protected function returnCleanData(array $npcData): array
    {
        $cleanData = [];

        foreach ($npcData as $key => $value) {
            if (! is_null($value)) {
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
