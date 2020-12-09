<?php

namespace Tests\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\User;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;

class LevelUpSkillsOnFakeUsersTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateClass, CreateRace, CreateGameMap, CreateGameSkill;

    public function setupCharacters() {
        $this->createClass();
        $this->createRace();
        $this->createGameMap();
        $this->createGameSkill();
        $this->createItem();

        $this->assertEquals(0, $this->artisan('create:fake-users', ['amount' => 1]));

        $this->assertTrue(User::all()->isNotEmpty());
        $this->assertTrue(Character::all()->isNotEmpty());
    }

    public function testLevelSkillOnFakeUserByTen() {

        $this->setupCharacters();

        $this->assertEquals(0, $this->artisan('level-skills:fake-users', ['amount' => 1, 'skillId' => 1, 'amountOfLevels' => 10]));
        
        $this->assertEquals(5, Character::first()->skills->find(1)->level);
    }

    public function testLevelSkillOnFakeUserByTenWhereSkillCannotTrain() {

        $this->setupCharacters();

        GameSkill::find(1)->update([
            'can_train' => false
        ]);

        $this->assertEquals(0, $this->artisan('level-skills:fake-users', ['amount' => 1, 'skillId' => 1, 'amountOfLevels' => 10]));
        
        $this->assertEquals(5, Character::first()->skills->find(1)->level);
    }

    public function testFailOnAmount() {

        $this->setupCharacters();

        $this->assertEquals(0, $this->artisan('level-skills:fake-users', ['amount' => -1, 'skillId' => 1, 'amountOfLevels' => 10]));

        $this->assertEquals(1, Character::first()->skills->find(1)->level);
    }


    public function testFailOnLevels() {

        $this->setupCharacters();

        $this->assertEquals(0, $this->artisan('level-skills:fake-users', ['amount' => 1, 'skillId' => 1, 'amountOfLevels' => -10]));

        $this->assertEquals(1, Character::first()->skills->find(1)->level);
    }

    public function testFailOnSkillId() {

        $this->setupCharacters();

        $this->assertEquals(0, $this->artisan('level-skills:fake-users', ['amount' => 1, 'skillId' => 12, 'amountOfLevels' => 10]));

        $this->assertEquals(1, Character::first()->skills->find(1)->level);
    }
}
