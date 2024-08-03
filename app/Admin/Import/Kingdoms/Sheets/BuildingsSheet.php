<?php

namespace App\Admin\Import\Kingdoms\Sheets;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\PassiveSkill;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class BuildingsSheet implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $buildingData = array_combine($rows[0]->toArray(), $row->toArray());

                $cleanData = $this->returnCleanBuildingData($buildingData);

                GameBuilding::updateOrCreate(['id' => $cleanData['id']], $cleanData);
            }
        }
    }

    protected function returnCleanBuildingData(array $buildingData)
    {
        $cleanData = [];

        foreach ($buildingData as $key => $value) {
            if (! is_null($value)) {

                if ($key === 'passive_skill_id') {
                    $passive = PassiveSkill::where('name', $value)->first();

                    if (! is_null($passive)) {
                        $value = $passive->id;
                    } else {
                        return $cleanData;
                    }
                }

                $cleanData[$key] = $value;
            } else {
                if ($key === 'is_locked') {
                    $cleanData[$key] = false;
                }
            }
        }

        return $cleanData;
    }
}
