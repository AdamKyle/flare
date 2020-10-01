<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Flare\Models\GameMap;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use App\Flare\Models\User;

class RegistrationTest extends TestCase
{
    use RefreshDatabase,
        CreateRace,
        CreateClass,
        CreateUser,
        CreateItem,
        CreateCharacter;

    public function setUp(): void {
        parent::setUp();

        $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
            'base_damage' => 3,
        ]);

        $path = Storage::disk('maps')->putFile('Surface', resource_path('maps/surface.jpg'));

        $gameMap = GameMap::create([
            'name'    => 'surface',
            'path'    => $path,
            'default' => true,
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();

        Storage::disk('maps')->deleteDirectory('Surface/');
    }

    public function testCanSeeRegistation() {
        $this->visit('/login')
             ->click('Register')
             ->see('E-Mail Address')
             ->see('Character Name')
             ->see('Password')
             ->see('Confirm Password')
             ->see('Character Info')
             ->see('Character Name')
             ->see('Choose a Race')
             ->see('Choose a class')
             ->see('Register');
    }

    public function testCanRegister() {
        $race  = $this->createRace([
            'dex_mod' => 2,
        ]);

        $class = $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $this->visit('/login')
             ->click('Register')
             ->submitForm('Register', [
                 'email'                 => 'a@example.net',
                 'password'              => 'TestExamplePassword',
                 'password_confirmation' => 'TestExamplePassword',
                 'name'                  => 'bob',
                 'race'                  => $race->id,
                 'class'                 => $class->id,
             ])->dontSee('The name has already been taken.');

      $user = User::first();

      $this->assertEquals('bob', $user->character->name);
      $this->assertEquals($race->name, $user->character->race->name);
      $this->assertEquals($class->name, $user->character->class->name);
    }

    public function testCannotRegisterWhenNoMap() {
        Storage::disk('maps')->deleteDirectory('Surface/');

        GameMap::first()->delete();

        $race  = $this->createRace([
            'dex_mod' => 2,
        ]);

        $class = $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $this->visit('/login')
             ->click('Register')
             ->submitForm('Register', [
                 'email'                 => 'a@example.net',
                 'password'              => 'TestExamplePassword',
                 'password_confirmation' => 'TestExamplePassword',
                 'name'                  => 'bob',
                 'race'                  => $race->id,
                 'class'                 => $class->id,
             ])->see('No game map has been set as default or created. Registration is disabled.');
    }

    public function testCannotRegisterWhenCharacterExists() {
        $race  = $this->createRace([
            'dex_mod' => 2,
        ]);

        $class = $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $this->createCharacter([
            'user_id' => $this->createUser()->id,
            'name'    => 'example'
        ]);

        $this->visit('/login')
             ->click('Register')
             ->submitForm('Register', [
                 'email'                 => 'a@example.net',
                 'password'              => 'TestExamplePassword',
                 'password_confirmation' => 'TestExamplePassword',
                 'name'                  => 'example',
                 'race'                  => $race->id,
                 'class'                 => $class->id,
             ])->see('The name has already been taken.');
    }
}
