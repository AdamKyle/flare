<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Setup\Character\CharacterFactory;

class LoginTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateRace,
        CreateClass,
        CreateCharacter;

    public function testAdminIsRedirectedToTheDashboard() {
        $user = (new CharacterFactory)->createBaseCharacter()->getUser();

        $this->createAdminRole('Admin');
        $user->assignRole('Admin');

        $this->visit('/login')
             ->submitForm('Login', [
                'email'    => $user->email,
                'password' => 'ReallyLongPassword',
             ])->see('Admin');
    }

    public function testPlayerIsRedirectedToTheGame() {
        $user = (new CharacterFactory)->createBaseCharacter()->getUser();

        $this->visit('/login')
             ->submitForm('Login', [
                'email'    => $user->email,
                'password' => 'ReallyLongPassword',
             ])->see('Manage Kingdoms');
    }

    public function testPlayerIsNotAllowedToLogin() {
        $user = (new CharacterFactory)->createBaseCharacter()->banCharacter()->getUser();

        $this->visit('/login')
             ->submitForm('Login', [
                'email'    => $user->email,
                'password' => 'ReallyLongPassword',
             ])->see('You have been banned until: For ever.');
    }
}
