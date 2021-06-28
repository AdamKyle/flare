<?php

namespace App\Admin\Import\Skills\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Flare\Models\GameSkill;

class SkillsSheet implements ToCollection {

    public function collection(Collection $rows) {

        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $skill = array_combine($rows[0]->toArray(), $row->toArray());

                $foundSkill = GameSkill::where('name', $skill['name'])->first();

                if (is_null($foundSkill)) {
                    GameSkill::create($skill);
                } else {
                    $foundSkill->update($skill);
                }
            }
        }
    }
}
