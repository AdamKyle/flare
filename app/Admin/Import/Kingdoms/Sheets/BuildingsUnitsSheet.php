<?php

namespace App\Admin\Import\Kingdoms\Sheets;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class BuildingsUnitsSheet implements ToCollection
{
    public function collection(Collection $rows)
    {
        GameBuildingUnit::query()->delete();

        foreach ($rows as $index => $row) {
            if ($index !== 0) {

                $gameBuilding = GameBuilding::where('name', $row[1])->first();
                $gameUnit = GameUnit::where('name', $row[2])->first();

                if (! is_null($gameUnit) && ! is_null($gameBuilding)) {
                    GameBuildingUnit::create([
                        'game_building_id' => $gameBuilding->id,
                        'game_unit_id' => $gameUnit->id,
                        'required_level' => $row[3],
                    ]);
                }
            }
        }
    }
}
