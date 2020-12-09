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
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateGameMap;

class CreateFakeUsersTest extends TestCase
{
    use RefreshDatabase, CreateItem, createGameSkill, CreateClass, CreateRace, CreateGameMap;

    public function testCreateOneFakeUser()
    {
        $this->createGameSkill();
        $this->createClass();
        $this->createRace();
        $this->createGameMap();

        $this->createItem();

        $this->assertEquals(0, $this->artisan('create:fake-users', ['amount' => 1]));

        $this->assertTrue(User::all()->isNotEmpty());
        $this->assertTrue(Character::all()->isNotEmpty());
    }

    public function testFailToCreateOneFakeUser()
    {
        $this->assertEquals(0, $this->artisan('create:fake-users', ['amount' => -1]));
    }
}
