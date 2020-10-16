<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;

class LoginTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateRace,
        CreateClass,
        CreateCharacter;

    public function testAdminIsRedirectedToTheDashboard() {
        $user = $this->createUser();

        $this->createAdminRole('Admin');
        $user->assignRole('Admin');

        $this->visit('/login')
             ->submitForm('Login', [
                'email'    => $user->email,
                'password' => 'password',
             ])->see('Admin');
    }

    public function testPlayerIsRedirectedToTheGame() {
        $user = $this->createUser();

        $this->createRace([
            'dex_mod' => 2,
        ]);

        $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $character = $this->createCharacter([
            'name'    => 'Apples',
            'user_id' => $user->id,
        ]);

        $this->visit('/login')
             ->submitForm('Login', [
                'email'    => $user->email,
                'password' => 'password',
             ])->see('Manage Kingdoms');
    }

    public function testPlayerIsNotAllowedToLogin() {
        $user = $this->createUser([
            'is_banned' => true,
        ]);

        $this->createRace([
            'dex_mod' => 2,
        ]);

        $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $character = $this->createCharacter([
            'name'    => 'Apples',
            'user_id' => $user->id,
        ]);

        $this->visit('/login')
             ->submitForm('Login', [
                'email'    => $user->email,
                'password' => 'password',
             ])->see('You have been banned until: For ever.');
    }
}
