<?php

namespace App\Admin\Import\Kingdoms\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;

class BuildingsUnitsSheet implements ToCollection {

    public function collection(Collection $rows) {
        GameBuildingUnit::query()->delete();

        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $buildingUnitData = [
                    'game_building_id'   => GameBuilding::where('name', $row[0])->first()->id,
                    'game_unit_id'       => GameUnit::where('name', $row[1])->first()->id,
                    'required_level'     => $row[2],
                ];

                GameBuildingUnit::create($buildingUnitData);
            }
        }
    }
}
