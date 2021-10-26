<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Setup\Character\CharacterFactory;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateRace,
        CreateClass,
        CreateCharacter;

    private $user;

    public function setUp(): void {
        parent::setUp();

        $this->user = (new CharacterFactory)->createBaseCharacter()->getUser();

        $this->createAdminRole('Admin');
        $this->user->assignRole('Admin');

        $this->app->make(PermissionRegistrar::class)->registerPermissions();
    }

    public function testAdminIsRedirectedToTheDashboard() {
        $this->visit('/login')
            ->submitForm('Login', [
                'email'    => $this->user->email,
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