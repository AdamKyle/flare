<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;

class RegistrationTest extends TestCase
{
    use RefreshDatabase,
        CreateRace,
        CreateClass,
        CreateUser,
        CreateItem,
        CreateCharacter,
        CreateGameSkill;

    public function setUp(): void {
        parent::setUp();

        $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
            'base_damage' => 3,
        ]);

        $gameMap = GameMap::create([
            'name'          => 'surface',
            'path'          => 'test path',
            'default'       => true,
            'kingdom_color' => '#ffffff',
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();
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
                 'name'                  => 'bobtest',
                 'race'                  => $race->id,
                 'class'                 => $class->id,
             ])->dontSee('The name has already been taken.');

      $user = User::first();

      $this->assertEquals('bobtest', $user->character->name);
      $this->assertEquals($race->name, $user->character->race->name);
      $this->assertEquals($class->name, $user->character->class->name);
    }

    public function testCannotRegisterWhenBanned() {
        $this->createUser(['is_banned' => true]);

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
                'name'                  => 'bobtest',
                'race'                  => $race->id,
                'class'                 => $class->id,
            ])->see('You have been banned until: ');
    }


    public function testCannotRegisterWhenNoMap() {

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

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->visit('/login')
             ->click('Register')
             ->submitForm('Register', [
                 'email'                 => 'apples@apples.com',
                 'password'              => 'ReallyLongPassword',
                 'password_confirmation' => 'ReallyLongPassword',
                 'name'                  => $character->name,
                 'race'                  => $race->id,
                 'class'                 => $class->id,
             ])->see('The name has already been taken.');
    }

    public function testCannotRegisterAnyMore() {
        $this->setupCharacters();

        $this->visit('/login')
            ->click('Register')
            ->submitForm('Register', [
                'email'                 => 'a@example.net',
                'password'              => 'TestExamplePassword',
                'password_confirmation' => 'TestExamplePassword',
                'name'                  => 'bobtest',
                'race'                  => GameRace::first()->id,
                'class'                 => GameClass::first()->id,
            ])->see('You cannot register anymore characters.');
    }

    protected function setupCharacters() {
        for ($i = 1; $i <= 10; $i++) {
            (new CharacterFactory())->createBaseCharacter();
        }
    }
}
