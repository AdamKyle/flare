<?php

namespace Tests\Console;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\User;
use Database\Seeders\CreateClasses;
use Database\Seeders\CreateRaces;
use Database\Seeders\GameSkillsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Storage;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class LevelUpSkillsOnFakeUsersTest extends TestCase
{
    use RefreshDatabase, CreateItem;

    public function setupCharacters() {
        $this->seed(GameSkillsSeeder::class);
        $this->seed(CreateClasses::class);
        $this->seed(CreateRaces::class);

        $path = Storage::disk('maps')->putFile('Surface', resource_path('maps/surface.jpg'));

        GameMap::create([
            'name'    => 'surface',
            'path'    => $path,
            'default' => true,
        ]);

        $this->createItem();

        $this->assertEquals(0, $this->artisan('create:fake-users', ['amount' => 1]));

        $this->assertTrue(User::all()->isNotEmpty());
        $this->assertTrue(Character::all()->isNotEmpty());

        Storage::disk('maps')->deleteDirectory('Surface/');
    }

    public function testLevelSkillOnFakeUserByTen() {

        $this->setupCharacters();

        $this->assertEquals(0, $this->artisan('level-skills:fake-users', ['amount' => 1, 'skillId' => 1, 'amountOfLevels' => 10]));
        
        $this->assertEquals(11, Character::first()->skills->find(1)->level);
    }

    public function testLevelSkillOnFakeUserByTenWhereSkillCannotTrain() {

        $this->setupCharacters();

        GameSkill::find(1)->update([
            'can_train' => false
        ]);

        $this->assertEquals(0, $this->artisan('level-skills:fake-users', ['amount' => 1, 'skillId' => 1, 'amountOfLevels' => 10]));
        
        $this->assertEquals(11, Character::first()->skills->find(1)->level);
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
