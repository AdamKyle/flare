<?php

namespace Tests\Unit\Flare\Calculators;

use Facades\App\Flare\Calculators\SkillXPCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateUser;

class SkillXpCalculatorTest extends TestCase
{
    use RefreshDatabase, CreateAdventure, CreateUser;

    public function setUp(): void {
        parent::setUp();
    }

    public function testSkillXpCalculator()
    {

        $character = (new CharacterFactory)->createBaseCharacter()
                                           ->givePlayerLocation()
                                           ->updateSkill('Looting', [
                                                'skill_bonus_per_level' => 10
                                           ])
                                           ->getCharacter(false);

        $xp = SkillXPCalculator::fetchSkillXP($character->skills->first(), $this->createNewAdventure());

        $this->assertTrue($xp > 0);
    }
}
