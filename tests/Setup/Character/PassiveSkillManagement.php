<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use Tests\Traits\CreateCharacterPassiveSkill;
use Tests\Traits\CreatePassiveSkill;

class PassiveSkillManagement
{
    use CreateCharacterPassiveSkill, CreatePassiveSkill;

    private Character $character;

    private CharacterFactory $characterFactory;

    public function __construct(Character $character, CharacterFactory $characterFactory)
    {
        $this->character = $character;
        $this->characterFactory = $characterFactory;
    }

    public function assignPassiveSkill(
        int $type,
        int $currentLevel = 0,
        array $passiveSkillOptions = [],
        array $characterPassiveSkillOptions = []
    ): PassiveSkillManagement {
        $passiveSkill = $this->createPassiveSkill(array_merge([
            'effect_type' => $type,
        ], $passiveSkillOptions));

        $this->createCharacterPassiveSkill(array_merge([
            'character_id' => $this->character->id,
            'passive_skill_id' => $passiveSkill->id,
            'parent_skill_id' => null,
            'current_level' => $currentLevel,
            'hours_to_next' => $passiveSkill->hours_per_level,
            'started_at' => null,
            'completed_at' => null,
            'is_locked' => false,
        ], $characterPassiveSkillOptions));

        $this->character = $this->character->refresh();

        return $this;
    }

    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    public function getCharacterFactory(): CharacterFactory
    {
        return $this->characterFactory;
    }
}
