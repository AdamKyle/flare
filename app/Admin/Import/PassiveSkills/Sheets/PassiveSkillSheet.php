<?php

namespace App\Admin\Import\PassiveSkills\Sheets;

use App\Flare\Models\PassiveSkill;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PassiveSkillSheet implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $data = array_combine($rows[0]->toArray(), $row->toArray());
                $data = $this->returnCleanData($data);

                if (! empty($data)) {
                    PassiveSkill::updateOrCreate(['id' => $data['id']], $data);
                }
            }
        }
    }

    protected function returnCleanData(array $passiveSkillData): array
    {
        $cleanData = [];

        foreach ($passiveSkillData as $key => $value) {
            if (is_null($value)) {
                if ($key === 'is_locked') {
                    $value = false;
                }

                if ($key === 'is_parent') {
                    $value = false;
                }

                $cleanData[$key] = $value;
            } else {
                if ($key === 'parent_skill_id') {
                    $passiveSkill = PassiveSkill::find($value);

                    if (is_null($passiveSkill)) {
                        $value = null;
                    } else {
                        $value = $passiveSkill->id;
                    }
                }

                $cleanData[$key] = $value;
            }
        }

        return $cleanData;
    }
}
