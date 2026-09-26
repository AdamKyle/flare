<?php

namespace Tests\Unit\Game\Skills\Calculators;

use App\Flare\Models\Skill;
use App\Game\Skills\Calculators\SkillXPCalculator;
use App\Game\Skills\Services\SkillBonusContextService;
use App\Game\Skills\Services\SkillBonusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateMonster;

class SkillXPCalculatorTest extends TestCase
{
    use CreateGameSkill, CreateMonster, RefreshDatabase;

    private ?SkillXPCalculator $skillXPCalculator;

    private ?Skill $skill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skillXPCalculator = new SkillXPCalculator(new SkillBonusService(new SkillBonusContextService));

        $gameSkill = $this->createGameSkill([
            'name' => 'XP Calculator Test Skill',
            'can_train' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter(assignPassiveSkills: false)
            ->assignSkill($gameSkill, 1, false, ['xp_towards' => 0.10])
            ->getCharacter();

        $this->skill = $character->skills->where('game_skill_id', $gameSkill->id)->first();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->skillXPCalculator = null;
        $this->skill = null;
    }

    public function test_monster_xp_below_one_thousand_contributes_its_reduced_share(): void
    {
        $monster = $this->createMonster(['xp' => 500]);

        $this->assertSame(455.0, $this->skillXPCalculator->fetchSkillXP($this->skill, $monster));
    }

    public function test_monster_xp_above_one_thousand_contributes_its_full_reduced_share(): void
    {
        $monster = $this->createMonster(['xp' => 1800]);

        $this->assertSame(1625.0, $this->skillXPCalculator->fetchSkillXP($this->skill, $monster));
    }

    public function test_monster_xp_share_rounding_to_zero_falls_back_to_full_monster_xp(): void
    {
        $this->skill->update(['xp_towards' => 1.0]);

        $monster = $this->createMonster(['xp' => 50]);

        $this->assertSame(55.0, $this->skillXPCalculator->fetchSkillXP($this->skill->refresh(), $monster));
    }

    public function test_missing_monster_contributes_no_monster_xp(): void
    {
        $this->assertSame(5.0, $this->skillXPCalculator->fetchSkillXP($this->skill));
    }
}
