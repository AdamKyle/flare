<?php

namespace Tests\Unit\Flare\Models;

use App\Game\Skills\Services\SkillBonusContextService;
use App\Game\Skills\Services\SkillBonusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateSkill;

class SkillClassBonusTest extends TestCase
{
    use CreateGameSkill, CreateSkill, RefreshDatabase;

    private ?SkillBonusService $skillBonusService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skillBonusService = new SkillBonusService(new SkillBonusContextService);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->skillBonusService = null;
    }

    public function test_accuracy_skill_receives_the_class_accuracy_modifier_only(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['accuracy_mod' => 0.15], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $baseSkill = $this->createGameSkill(['name' => 'Accuracy', 'skill_bonus_per_level' => 0.0]);
        $skill = $this->createSkill(['character_id' => $character->id, 'game_skill_id' => $baseSkill->id, 'level' => 1]);

        $this->assertSame(0.15, $this->skillBonusService->skillBonus($skill));
    }

    public function test_looting_skill_receives_the_class_looting_modifier_only(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['looting_mod' => 0.20], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $baseSkill = $this->createGameSkill(['name' => 'Looting', 'skill_bonus_per_level' => 0.0]);
        $skill = $this->createSkill(['character_id' => $character->id, 'game_skill_id' => $baseSkill->id, 'level' => 1]);

        $this->assertSame(0.20, $this->skillBonusService->skillBonus($skill));
    }

    public function test_dodge_skill_receives_the_class_dodge_modifier_only(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['dodge_mod' => 0.10], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $baseSkill = $this->createGameSkill(['name' => 'Dodge', 'skill_bonus_per_level' => 0.0]);
        $skill = $this->createSkill(['character_id' => $character->id, 'game_skill_id' => $baseSkill->id, 'level' => 1]);

        $this->assertSame(0.10, $this->skillBonusService->skillBonus($skill));
    }

    public function test_null_class_skill_modifier_contributes_zero(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['accuracy_mod' => null], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $baseSkill = $this->createGameSkill(['name' => 'Accuracy', 'skill_bonus_per_level' => 0.0]);
        $skill = $this->createSkill(['character_id' => $character->id, 'game_skill_id' => $baseSkill->id, 'level' => 1]);

        $this->assertSame(0.0, $this->skillBonusService->skillBonus($skill));
    }
}
