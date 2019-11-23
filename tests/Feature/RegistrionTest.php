<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use App\User;

class RegistrationTest extends TestCase
{
    use RefreshDatabase,
        CreateRace,
        CreateClass,
        CreateUser,
        CreateCharacter;

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
