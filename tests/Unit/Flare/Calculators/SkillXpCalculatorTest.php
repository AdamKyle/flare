<?php

namespace Tests\Unit\Flare\Calculators;

use Database\Seeders\GameSkillsSeeder;
use Facades\App\Flare\Calculators\SkillXPCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\CharacterSetup;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateUser;

class SkillXpCalculatorTest extends TestCase
{
    use RefreshDatabase, CreateAdventure, CreateUser;

    public function setUp(): void {
        parent::setUp();

        $this->seed(GameSkillsSeeder::class);
    }

    public function testSkillXpCalculator()
    {

        $character = (new CharacterSetup)->setupCharacter($this->createUser())
                                         ->setSkill('Looting', ['skill_bonus_per_level' => 10], [], true)
                                         ->getCharacter();

        $xp = SkillXPCalculator::fetchSkillXP($character->skills->first(), $this->createNewAdventure());

        $this->assertTrue($xp > 0);
    }
}
