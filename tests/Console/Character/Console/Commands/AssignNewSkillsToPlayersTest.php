<?php

namespace Tests\Console\Character\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;

class AssignNewSkillsToPlayersTest extends TestCase
{
    use CreateGameSkill, RefreshDatabase;

    private ?CharacterFactory $character;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testAssignNewSkillToPlayer()
    {
        $gameSkill = $this->createGameSkill([
            'name' => 'Sample Skill',
        ]);

        $character = $this->character->getCharacter();

        Artisan::call('assign:new-skills');

        $character = $character->refresh();

        $this->assertNotNull($character->skills()->where('game_skill_id', $gameSkill->id)->first());
    }
}
