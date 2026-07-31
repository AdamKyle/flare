<?php

namespace App\Game\PassiveSkills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\Core\Services\GameTimerService;
use App\Game\PassiveSkills\Jobs\TrainPassiveSkill;

class PassiveSkillTrainingService
{
    private CharacterPassiveSkills $characterPassiveSkills;

    public function __construct(
        CharacterPassiveSkills $characterPassiveSkills,
        private readonly GameTimerService $gameTimerService,
    ) {
        $this->characterPassiveSkills = $characterPassiveSkills;
    }

    /**
     * Train a passive skill.
     */
    public function trainSkill(CharacterPassiveSkill $skill, Character $character): bool
    {
        if ($skill->current_level >= $skill->passiveSkill->max_level) {
            $skill->update([
                'current_level' => $skill->passiveSkill->max_level,
                'hours_to_next' => 0,
                'started_at' => null,
                'completed_at' => null,
            ]);

            return false;
        }

        $time = $this->gameTimerService->availableAtFromHours($skill->hours_to_next);

        $skill->update([
            'started_at' => now(),
            'completed_at' => $time,
        ]);

        $skill = $skill->refresh();

        $delayTime = $this->gameTimerService->availableAtFromMinutes(15);

        $character = $character->refresh();

        TrainPassiveSkill::dispatch($character, $skill)->delay($delayTime);

        event(new UpdateCharacterBaseDetailsEvent($character));

        return true;
    }
}
