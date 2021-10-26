<?php

namespace App\Admin\Services;

use App\Admin\Jobs\AssignSkillJob;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use Facades\App\Flare\Values\UserOnlineValue;

class AssignSkillService {

    public function assignSkills() {
        $gameSkills = GameSkill::all();

        forEach($gameSkills as $skill) {
            foreach (Character::all() as $character) {
                $hasSkill = $character->skills()->where('game_skill_id', $skill->id)->first();

                if (is_null($hasSkill)) {
                    if (!is_null($skill->gameClass)) {
                        if ($character->class->id === $skill->game_class_id) {
                            AssignSkillJob::dispatch($character->id, $skill->id);
                        }
                    } else {
                        AssignSkillJob::dispatch($character->id, $skill->id);
                    }
                }
            }
        }
    }
}
