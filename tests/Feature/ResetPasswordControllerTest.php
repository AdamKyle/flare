<?php

namespace Tests\Feature;

use Cache;
use Hash;
use Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;

class ResetPasswordControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole;

    public function testCanSeeResetPasswordPage() {
        $user = $this->createUser();

        $this->createAdminRole('Admin');
        $user->assignRole('Admin');

        $this->visit(route('password.reset', app('Password')::getRepository()->create($user)))
             ->see('Reset Password');
    }

    protected function createUserWithCharacter() {
        return (new CharacterFactory)->createBaseCharacter();
    }
}
