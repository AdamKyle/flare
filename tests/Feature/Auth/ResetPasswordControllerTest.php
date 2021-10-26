<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;

class ResetPasswordControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole;

    public function testCanSeeResetPasswordPage() {
        $user = $this->createUser();

        $this->createAdminRole();
        $user->assignRole('Admin');

        $this->visit(route('password.reset', app('Password')::getRepository()->create($user)))
            ->see('Reset Password');
    }
}