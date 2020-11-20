<?php

namespace Tests\Feature;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use App\Flare\Models\User;
use Database\Seeders\CreateClasses;
use Database\Seeders\CreateRaces;
use Database\Seeders\GameSkillsSeeder;
use Str;

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
                 'name'                  => 'bobtest',
                 'race'                  => $race->id,
                 'class'                 => $class->id,
                 'question_one'          => 'Whats your favourite movie?',
                 'question_two'          => 'Whats the name of the town you grew up in?', 
                 'answer_one'            => 'test',
                 'answer_two'            => 'test2',
             ])->dontSee('The name has already been taken.');

      $user = User::first();

      $this->assertEquals('bobtest', $user->character->name);
      $this->assertEquals($race->name, $user->character->race->name);
      $this->assertEquals($class->name, $user->character->class->name);
    }

    public function testSecurityQuestionsMustBeUnique() {
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
                 'question_one'          => 'Whats your favourite movie?',
                 'question_two'          => 'Whats your favourite movie?', 
                 'answer_one'            => 'test',
                 'answer_two'            => 'test2',
             ])->see('Security questions need to be unique.');
    }

    public function testSecurityAnswersMustBeUnique() {
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
                 'question_one'          => 'Whats your favourite movie?',
                 'question_two'          => 'Whats the name of the town you grew up in?', 
                 'answer_one'            => 'test',
                 'answer_two'            => 'test',
             ])->see('Security questions answers need to be unique.');
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

    public function testCannotRegisterWhenCharacterBanned() {
        $race  = $this->createRace([
            'dex_mod' => 2,
        ]);

        $class = $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $user = $this->createUser();

        $user->update([
            'is_banned' => true,
        ]);

        $this->createCharacter([
            'user_id' => $user->id,
            'name'    => 'example'
        ]);

        $this->visit('/login')
             ->click('Register')
             ->submitForm('Register', [
                 'email'                 => 'a@example.net',
                 'password'              => 'TestExamplePassword',
                 'password_confirmation' => 'TestExamplePassword',
                 'name'                  => Str::random(10),
                 'race'                  => $race->id,
                 'class'                 => $class->id,
                 'question_one'          => 'Whats your favourite movie?',
                 'question_two'          => 'Whats the name of the town you grew up in?', 
                 'answer_one'            => 'test',
                 'answer_two'            => 'test2',
             ])->see('You has been banned until: For ever.');
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
                'question_one'          => 'Whats your favourite movie?',
                'question_two'          => 'Whats the name of the town you grew up in?', 
                'answer_one'            => 'test',
                'answer_two'            => 'test2',
            ])->see('You cannot register anymore characters.');
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

        $this->assertEquals(0, $this->artisan('create:fake-users', ['amount' => 10]));

        $this->assertTrue(User::all()->isNotEmpty());
        $this->assertTrue(Character::all()->isNotEmpty());

        Storage::disk('maps')->deleteDirectory('Surface/');
    }
}
