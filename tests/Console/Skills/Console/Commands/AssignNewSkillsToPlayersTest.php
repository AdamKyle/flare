<?php

namespace Tests\Console\Skills\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;

class AssignNewSkillsToPlayersTest extends TestCase
{
    use CreateGameSkill, RefreshDatabase;

    public function test_command_assigns_new_skill_to_character()
    {
        $gameSkill = $this->createGameSkill(['name' => 'Sample Skill']);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->artisan('assign:new-skills');

        $character = $character->refresh();

        $this->assertNotNull($character->skills()->where('game_skill_id', $gameSkill->id)->first());
    }

    public function test_command_runs_successfully_with_no_characters()
    {
        $this->assertEquals(0, $this->artisan('assign:new-skills'));
    }

    public function test_command_does_not_duplicate_skills_when_run_twice()
    {
        $gameSkill = $this->createGameSkill(['name' => 'Duplicate Skill']);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->artisan('assign:new-skills');
        $this->artisan('assign:new-skills');

        $character = $character->refresh();

        $this->assertEquals(1, $character->skills()->where('game_skill_id', $gameSkill->id)->count());
    }

    public function test_command_assigns_skills_to_all_characters()
    {
        $gameSkill = $this->createGameSkill(['name' => 'Multi Skill']);
        $characterOne = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $characterTwo = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->artisan('assign:new-skills');

        $this->assertNotNull($characterOne->refresh()->skills()->where('game_skill_id', $gameSkill->id)->first());
        $this->assertNotNull($characterTwo->refresh()->skills()->where('game_skill_id', $gameSkill->id)->first());
    }
}
