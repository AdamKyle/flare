<?php

namespace App\Game\PassiveSkills\Services;

use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\PassiveSkills\Events\UpdatePassiveTree;
use App\Game\PassiveSkills\Jobs\TrainPassiveSkill;

class PassiveSkillTrainingService {

    private $characterPassiveSkills;

    public function __construct(CharacterPassiveSkills $characterPassiveSkills) {
        $this->characterPassiveSkills = $characterPassiveSkills;
    }

    public function trainSkill(CharacterPassiveSkill $skill, Character $character) {

        $time = now()->addHours($skill->hours_to_next);

        $skill->update([
            'started_at'   => now(),
            'completed_at' => $time
        ]);

        $skill = $skill->refresh();

        $delayTime = now()->addMinutes(15);

        $character = $character->refresh();

        TrainPassiveSkill::dispatch($character, $skill)->delay($delayTime);

        event(new UpdateTopBarEvent($character));
    }
}
