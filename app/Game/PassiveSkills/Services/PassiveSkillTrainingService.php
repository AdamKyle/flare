<?php

namespace App\Game\PassiveSkills\Services;

use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\PassiveSkills\Jobs\TrainPassiveSkill;

class PassiveSkillTrainingService {

    /**
     * @var CharacterPassiveSkills $characterPassiveSkills
     */
    private CharacterPassiveSkills $characterPassiveSkills;

    /**
     * @param CharacterPassiveSkills $characterPassiveSkills
     */
    public function __construct(CharacterPassiveSkills $characterPassiveSkills) {
        $this->characterPassiveSkills = $characterPassiveSkills;
    }

    /**
     * Train a passive skill.
     *
     * @param CharacterPassiveSkill $skill
     * @param Character $character
     * @return void
     */
    public function trainSkill(CharacterPassiveSkill $skill, Character $character): void {

        $time = now()->addHours($skill->hours_to_next);

        $skill->update([
            'started_at'   => now(),
            'completed_at' => $time,
        ]);

        $skill = $skill->refresh();

        $delayTime = now()->addMinutes(15);

        $character = $character->refresh();

        TrainPassiveSkill::dispatch($character, $skill)->delay($delayTime);

        event(new UpdateTopBarEvent($character));
    }
}
