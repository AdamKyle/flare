<?php

namespace App\Admin\Import\Kingdoms\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Flare\Models\GameBuildingUnit;

class BuildingsUnitsSheet implements ToCollection {

    public function collection(Collection $rows) {
        GameBuildingUnit::query()->delete();

        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $buildingUnitData = [
                    'game_building_id'   => $row[1],
                    'game_unit_id'       => $row[2],
                    'required_level'     => $row[3],
                ];

                $gameBuildingUnit = GameBuildingUnit::find($row[0]);

                if (!is_null($gameBuildingUnit)) {
                    $gameBuildingUnit->update($buildingUnitData);
                } else {
                    GameBuildingUnit::create($buildingUnitData);
                }
            }
        }
    }
}
