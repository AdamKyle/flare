<?php

namespace App\Admin\Import\Skills\Sheets;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class SkillsSheet implements ToCollection
{
    public function collection(Collection $rows)
    {

        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $skill = array_combine($rows[0]->toArray(), $row->toArray());

                if (is_null($skill['can_train'])) {
                    $skill['can_train'] = false;
                }

                if (is_null($skill['is_locked'])) {
                    $skill['is_locked'] = false;
                }

                if (! is_null($skill['game_class_id'])) {
                    $class = GameClass::find($skill['game_class_id']);

                    if (! is_null($class)) {
                        $skill['game_class_id'] = $class->id;
                    } else {
                        unset($skill['game_class_id']);
                    }
                }

                $foundSkill = GameSkill::find($skill['id']);

                if (is_null($foundSkill)) {
                    GameSkill::create($skill);
                } else {
                    $foundSkill->update($skill);
                }
            }
        }
    }
}
