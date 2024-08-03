<?php

namespace Tests\Feature\Auth;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class RegistrationControllerTest extends TestCase
{
    use CreateCharacter,
        CreateClass,
        CreateGameSkill,
        CreateItem,
        CreatePassiveSkill,
        CreateRace,
        CreateUser,
        RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
            'base_damage' => 3,
        ]);

        $this->createPassiveSkill();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testCanSeeRegistration()
    {
        $this->visit('/login')
            ->click('Register')
            ->see('E-Mail Address')
            ->see('Character Name')
            ->see('Password')
            ->see('Confirm Password')
            ->see('Character Creation')
            ->see('Character Name')
            ->see('Choose a Race')
            ->see('Choose a class')
            ->see('Register');
    }

    public function testCanRegister()
    {
        GameMap::create([
            'name' => 'Surface',
            'path' => 'test path',
            'default' => true,
            'kingdom_color' => '#ffffff',
        ]);

        $race = $this->createRace([
            'dex_mod' => 2,
        ]);

        $class = $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $this->visit('/login')
            ->click('Register')
            ->submitForm('Register', [
                'email' => 'a@example.net',
                'password' => 'TestExamplePassword',
                'password_confirmation' => 'TestExamplePassword',
                'name' => 'bobtest',
                'race' => $race->id,
                'class' => $class->id,
            ])->dontSee('The name has already been taken.');

        $user = User::where('email', 'a@example.net')->first();

        $this->assertEquals('bobtest', $user->character->name);
        $this->assertEquals($race->name, $user->character->race->name);
        $this->assertEquals($class->name, $user->character->class->name);
    }

    public function testCannotRegisterWhenBanned()
    {
        GameMap::create([
            'name' => 'Surface',
            'path' => 'test path',
            'default' => true,
            'kingdom_color' => '#ffffff',
        ]);

        $this->createUser(['is_banned' => true]);

        $race = $this->createRace([
            'dex_mod' => 2,
        ]);

        $class = $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $this->visit('/login')
            ->click('Register')
            ->submitForm('Register', [
                'email' => 'a@example.net',
                'password' => 'TestExamplePassword',
                'password_confirmation' => 'TestExamplePassword',
                'name' => 'bobtest',
                'race' => $race->id,
                'class' => $class->id,
            ])->see('You have been banned until: ');
    }

    public function testCannotRegisterWhenNoMap()
    {

        $race = $this->createRace([
            'dex_mod' => 2,
        ]);

        $class = $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $this->visit('/login')
            ->click('Register')
            ->submitForm('Register', [
                'email' => 'a@example.net',
                'password' => 'TestExamplePassword',
                'password_confirmation' => 'TestExamplePassword',
                'name' => 'TestExample',
                'race' => $race->id,
                'class' => $class->id,
            ])->see('No game map has been set as default or created. Registration is disabled.');
    }

    public function testCannotRegisterWhenCharacterExists()
    {
        GameMap::create([
            'name' => 'Surface',
            'path' => 'test path',
            'default' => true,
            'kingdom_color' => '#ffffff',
        ]);

        $race = $this->createRace([
            'dex_mod' => 2,
        ]);

        $class = $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter(false);

        $this->visit('/login')
            ->click('Register')
            ->submitForm('Register', [
                'email' => 'apples@apples.com',
                'password' => 'ReallyLongPassword',
                'password_confirmation' => 'ReallyLongPassword',
                'name' => $character->name,
                'race' => $race->id,
                'class' => $class->id,
            ])->see('The name has already been taken.');
    }

    public function testCannotRegisterAnyMore()
    {
        GameMap::create([
            'name' => 'Surface',
            'path' => 'test path',
            'default' => true,
            'kingdom_color' => '#ffffff',
        ]);

        $this->setupCharacters();

        $this->visit('/login')
            ->click('Register')
            ->submitForm('Register', [
                'email' => 'a@example.net',
                'password' => 'TestExamplePassword',
                'password_confirmation' => 'TestExamplePassword',
                'name' => 'bobtest',
                'race' => GameRace::first()->id,
                'class' => GameClass::first()->id,
            ])->see('You cannot register anymore characters.');
    }

    protected function setupCharacters()
    {
        for ($i = 1; $i <= 10; $i++) {
            (new CharacterFactory)->createBaseCharacter();
        }
    }
}
