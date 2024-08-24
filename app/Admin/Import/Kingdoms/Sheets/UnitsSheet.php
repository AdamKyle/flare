<?php

namespace App\Admin\Import\Kingdoms\Sheets;

use App\Flare\Models\GameUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class UnitsSheet implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $unitData = array_combine($rows[0]->toArray(), $row->toArray());

                $cleanData = $this->returnCleanUnitData($unitData);

                GameUnit::updateOrCreate(['id' => $cleanData['id']], $cleanData);
            }
        }
    }

    protected function returnCleanUnitData(array $unitData)
    {
        $cleanData = [];

        foreach ($unitData as $key => $value) {
            if (! is_null($value)) {
                $cleanData[$key] = $value;
            }
        }

        return $cleanData;
    }
}
