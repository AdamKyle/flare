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

class LevelFakeUsersTest extends TestCase
{
    use RefreshDatabase, CreateItem;

    public function testLevelFakeUserByTen() {

        $this->setupCharacters();

        $this->assertEquals(0, $this->artisan('level-up:fake-users', ['amount' => 1, 'amountOfLevels' => 10]));

        $this->assertEquals(11, Character::first()->level);
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
}
