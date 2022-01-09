<?php

namespace App\Admin\Import\Kingdoms\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Flare\Models\GameUnit;

class UnitsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $unitData = array_combine($rows[0]->toArray(), $row->toArray());

                $cleanData = $this->returnCleanUnitData($unitData);

                GameUnit::updateOrCreate(['name' => $cleanData['name']], $cleanData);
            }
        }
    }

    protected function returnCleanUnitData(array $unitData) {
        $cleanData = [];

        foreach ($unitData as $key => $value) {
            if (!is_null($value)) {
                $cleanData[$key] = $value;
            }
        }

        return $cleanData;
    }
}
