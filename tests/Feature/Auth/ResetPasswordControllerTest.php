<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ResetPasswordControllerTest extends TestCase
{
    use CreateRole,
        CreateUser,
        RefreshDatabase;

    public function test_can_see_reset_password_page()
    {
        $user = $this->createUser();

        $this->createAdminRole();
        $user->assignRole('Admin');

        $this->visit(route('password.reset', app('Password')::getRepository()->create($user)))
            ->see('Reset Password');
    }
}
