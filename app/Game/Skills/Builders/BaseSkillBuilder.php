<?php

namespace App\Game\Skills\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Core\Chance\RandomNumberGenerator;

class BaseSkillBuilder
{
    public function __construct(private readonly RandomNumberGenerator $randomNumberGenerator) {}

    /**
     * Get the base character skill value for a character.
     */
    public function getBaseCharacterSkillValue(Character $character, GameSkill $skill): array
    {
        return [
            'character_id' => $character->id,
            'game_skill_id' => $skill->id,
            'currently_training' => false,
            'level' => 1,
            'xp' => 0,
            'xp_max' => $skill->can_train ? 100 : $this->randomNumberGenerator->numberBetween(100, 350),
            'is_locked' => $skill->is_locked,
            'skill_type' => $skill->type,
        ];
    }
}
