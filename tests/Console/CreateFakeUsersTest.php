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

class CreateFakeUsersTest extends TestCase
{
    use RefreshDatabase, CreateItem;

    public function testCreateOneFakeUser()
    {
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

    public function testFailToCreateOneFakeUser()
    {
        $this->assertEquals(0, $this->artisan('create:fake-users', ['amount' => -1]));
    }
}
