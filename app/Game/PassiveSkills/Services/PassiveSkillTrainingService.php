<?php

namespace App\Game\PassiveSkills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\Core\Services\GameTimerService;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Values\AreaGemRewardEffect;
use App\Game\PassiveSkills\Jobs\TrainPassiveSkill;

class PassiveSkillTrainingService
{
    private CharacterPassiveSkills $characterPassiveSkills;

    public function __construct(
        CharacterPassiveSkills $characterPassiveSkills,
        private readonly GameTimerService $gameTimerService,
        private readonly CharacterAreaGemEffectService $characterAreaGemEffectService,
    ) {
        $this->characterPassiveSkills = $characterPassiveSkills;
    }

    /**
     * Train the given passive skill for the character, applying the Gem-resolved training reduction.
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

        $reduction = min($this->characterAreaGemEffectService->resolveForCharacter($character)->rewardEffect(AreaGemRewardEffect::KINGDOM_PASSIVE_TRAINING_REDUCTION), 0.99);
        $hoursToNext = max($skill->hours_to_next * (1 - $reduction), 0);

        $time = $this->gameTimerService->availableAtFromHours($hoursToNext);

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
