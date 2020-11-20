<?php

namespace Tests\Console;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\User;
use Database\Seeders\CreateClasses;
use Database\Seeders\CreateRaces;
use Database\Seeders\GameSkillsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Storage;
use Tests\Setup\CharacterSetup;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class GiveCharacterGold extends TestCase
{
    use RefreshDatabase, CreateUser;

    public function testGiveCharacterGold()
    {
        $this->seed(GameSkillsSeeder::class);
        
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter($user)->getCharacter();


        $this->assertEquals(0, $this->artisan('give:gold', ['characterId' => 1, 'amount' => 100]));

        $this->assertEquals(100, $character->refresh()->gold);
    }

    public function testFailToGiveGold()
    {
        $this->seed(GameSkillsSeeder::class);
        
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter($user)->getCharacter();


        $this->assertEquals(0, $this->artisan('give:gold', ['characterId' => 100, 'amount' => 1000]));

        $this->assertEquals(10, $character->refresh()->gold);
    }
}
