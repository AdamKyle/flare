<?php

namespace Tests\Console;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Character;
use App\Flare\Models\User;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;

class LevelFakeUsersTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateRace, CreateClass, CreateGameMap, CreateGameSkill;

    public function testLevelFakeUserByTen() {

        $this->setupCharacters();

        $this->assertEquals(0, $this->artisan('level-up:fake-users', ['amount' => 1, 'amountOfLevels' => 10]));

        $this->assertEquals(11, Character::first()->level);
    }

    public function testFailToLevelFakeUserByTen() {
        $this->expectException(Exception::class);

        $this->assertEquals(0, $this->artisan('level-up:fake-users', ['amount' => 1, 'amountOfLevels' => 10]));
    }

    public function testFailOnAmount() {

        $this->setupCharacters();

        $this->assertEquals(0, $this->artisan('level-up:fake-users', ['amount' => -10, 'amountOfLevels' => 10]));

        $this->assertEquals(1, Character::first()->level);
    }


    public function testFailOnLevels() {

        $this->setupCharacters();

        $this->assertEquals(0, $this->artisan('level-up:fake-users', ['amount' => 1, 'amountOfLevels' => -10]));

        $this->assertEquals(1, Character::first()->level);
    }

    public function testFailOnNoCharacters() {
        $this->assertEquals(0, $this->artisan('level-up:fake-users', ['amount' => 0, 'amountOfLevels' => 10]));
    }

    protected function setupCharacters() {
        $this->createGameSkill();
        $this->createGameMap();
        $this->createRace();
        $this->createClass();

        $this->createItem();

        $this->assertEquals(0, $this->artisan('create:fake-users', ['amount' => 1]));

        $this->assertTrue(User::all()->isNotEmpty());
        $this->assertTrue(Character::all()->isNotEmpty());
    }
}
