<?php

namespace Tests\Feature\Game\Core;

use Database\Seeders\GameSkillsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\CharacterSetup;
use Tests\TestCase;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateUser;

class MonstersControllerTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateMonster;

    public function testCanSeeMonster() {
        $this->seed(GameSkillsSeeder::class);

        $monster = $this->createMonster();

        $character = (new CharacterSetup())
                        ->setupCharacter($this->createUser())
                        ->getCharacter();
        
        $this->actingAs($character->user)->visitRoute('game.monsters.monster', [
            'monster' => $monster,
        ])->see($monster->name);
    }
}
