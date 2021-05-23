<?php

namespace App\Admin\Import\Kingdoms\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Flare\Models\GameBuilding;

class BuildingsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $buildingData = array_combine($rows[0]->toArray(), $row->toArray());

                GameBuilding::create($this->returnCleanBuildingData($buildingData));
            }
        }
    }

    protected function returnCleanBuildingData(array $buildingData) {
        $cleanData = [];

        foreach ($buildingData as $key => $value) {
            if (!is_null($value)) {
                $cleanData[$key] = $value;
            }
        }

        return $cleanData;
    }
}
