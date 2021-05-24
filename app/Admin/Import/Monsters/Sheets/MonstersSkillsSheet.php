<?php

namespace App\Admin\Import\Monsters\Sheets;

use App\Flare\Models\Monster;
use App\Flare\Models\Skill;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Flare\Models\GameSkill;

class MonstersSkillsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $monsterSkill = array_combine($rows[0]->toArray(), $row->toArray());

                $monsterSkill = $this->returnCleanSkill($monsterSkill);

                if (is_null($monsterSkill)) {
                    continue;
                }

                $foundSkill = Skill::where('monster_id', $monsterSkill['monster_id'])
                                   ->where('game_skill_id', $monsterSkill['game_skill_id'])
                                   ->first();

                if (is_null($foundSkill)) {
                    Skill::create($monsterSkill);
                } else {
                    $foundSkill->update();
                }
            }
        }
    }

    protected function returnCleanSkill(array $skill) {
        $cleanData = [];

        foreach ($skill as $key => $value) {
            if (!is_null($value)) {

                if ($key === 'monster_id') {
                    $monster = Monster::where('name', $value)->first();

                    if (is_null($monster)) {
                        return null;
                    } else {
                        $value = $monster->id;
                    }
                } else if ($key === 'game_skill_id') {
                    $skill = GameSkill::where('name', $value)->first();

                    if (is_null($skill)) {
                        return null;
                    } else {
                        $value = $skill->id;
                    }
                }

                $cleanData[$key] = $value;
            }
        }

        return $cleanData;
    }
}
