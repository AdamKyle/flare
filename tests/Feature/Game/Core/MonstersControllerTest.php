<?php

namespace Tests\Feature\Game\Core;

use Database\Seeders\GameSkillsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateUser;

class MonstersControllerTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateMonster;

    public function testCanSeeMonster() {

        $monster = $this->createMonster();

        $user = (new CharacterFactory)
                        ->createBaseCharacter()
                        ->getUser();
        
        $this->actingAs($user)->visitRoute('game.monsters.monster', [
            'monster' => $monster,
        ])->see($monster->name);
    }
}
