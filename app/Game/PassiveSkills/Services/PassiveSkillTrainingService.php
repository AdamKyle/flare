<?php

namespace App\Game\PassiveSkills\Services;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Game\PassiveSkills\Events\UpdatePassiveSkillTimer;
use App\Game\PassiveSkills\Jobs\TrainPassiveSkill;

class PassiveSkillTrainingService {

    public function trainSkill(CharacterPassiveSkill $skill, Character $character) {

        $time = now()->addMinutes(1);

        $skill->update([
            'started_at'   => now(),
            'completed_at' => $time
        ]);

        $skill = $skill->refresh();

        TrainPassiveSkill::dispatch($character, $skill)->delay($time);

        event(new UpdateTopBarEvent($character->refresh()));
    }
}