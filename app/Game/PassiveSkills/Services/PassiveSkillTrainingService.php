<?php

namespace App\Game\PassiveSkills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\PassiveSkills\Jobs\TrainPassiveSkill;

class PassiveSkillTrainingService
{
    private CharacterPassiveSkills $characterPassiveSkills;

    public function __construct(CharacterPassiveSkills $characterPassiveSkills)
    {
        $this->characterPassiveSkills = $characterPassiveSkills;
    }

    /**
     * Train a passive skill.
     */
    public function trainSkill(CharacterPassiveSkill $skill, Character $character): void
    {
        $time = now()->addHours($skill->hours_to_next);

        if (env('APP_ENV') === 'local') {
            $time = now()->addMinute();
        }

        $skill->update([
            'started_at' => now(),
            'completed_at' => $time,
        ]);

        $skill = $skill->refresh();

        $delayTime = now()->addMinutes(15);

        if (env('APP_ENV') === 'local') {
            $delayTime = now()->addMinute();
        }

        $character = $character->refresh();

        TrainPassiveSkill::dispatch($character, $skill)->delay($delayTime);

        event(new UpdateCharacterBaseDetailsEvent($character));
    }
}
