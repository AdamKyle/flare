<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateSkill;

class SkillClassBonusTest extends TestCase
{
    use CreateGameSkill, CreateSkill, RefreshDatabase;

    public function test_accuracy_skill_receives_the_class_accuracy_modifier_only(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['accuracy_mod' => 0.15], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $baseSkill = $this->createGameSkill(['name' => 'Accuracy', 'skill_bonus_per_level' => 0.0]);
        $skill = $this->createSkill(['character_id' => $character->id, 'game_skill_id' => $baseSkill->id, 'level' => 1]);

        $this->assertSame(0.15, $skill->skill_bonus);
    }

    public function test_looting_skill_receives_the_class_looting_modifier_only(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['looting_mod' => 0.20], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $baseSkill = $this->createGameSkill(['name' => 'Looting', 'skill_bonus_per_level' => 0.0]);
        $skill = $this->createSkill(['character_id' => $character->id, 'game_skill_id' => $baseSkill->id, 'level' => 1]);

        $this->assertSame(0.20, $skill->skill_bonus);
    }

    public function test_dodge_skill_receives_the_class_dodge_modifier_only(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['dodge_mod' => 0.10], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $baseSkill = $this->createGameSkill(['name' => 'Dodge', 'skill_bonus_per_level' => 0.0]);
        $skill = $this->createSkill(['character_id' => $character->id, 'game_skill_id' => $baseSkill->id, 'level' => 1]);

        $this->assertSame(0.10, $skill->skill_bonus);
    }
}
