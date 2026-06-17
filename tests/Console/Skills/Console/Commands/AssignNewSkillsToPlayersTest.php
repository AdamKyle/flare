<?php

namespace Tests\Console\Skills\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;

class AssignNewSkillsToPlayersTest extends TestCase
{
    use CreateGameSkill, RefreshDatabase;

    public function testCommandAssignsNewSkillToCharacter()
    {
        $gameSkill = $this->createGameSkill(['name' => 'Sample Skill']);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->artisan('assign:new-skills');

        $character = $character->refresh();

        $this->assertNotNull($character->skills()->where('game_skill_id', $gameSkill->id)->first());
    }

    public function testCommandRunsSuccessfullyWithNoCharacters()
    {
        $this->assertEquals(0, $this->artisan('assign:new-skills'));
    }

    public function testCommandDoesNotDuplicateSkillsWhenRunTwice()
    {
        $gameSkill = $this->createGameSkill(['name' => 'Duplicate Skill']);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->artisan('assign:new-skills');
        $this->artisan('assign:new-skills');

        $character = $character->refresh();

        $this->assertEquals(1, $character->skills()->where('game_skill_id', $gameSkill->id)->count());
    }

    public function testCommandAssignsSkillsToAllCharacters()
    {
        $gameSkill = $this->createGameSkill(['name' => 'Multi Skill']);
        $characterOne = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $characterTwo = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->artisan('assign:new-skills');

        $this->assertNotNull($characterOne->refresh()->skills()->where('game_skill_id', $gameSkill->id)->first());
        $this->assertNotNull($characterTwo->refresh()->skills()->where('game_skill_id', $gameSkill->id)->first());
    }
}
