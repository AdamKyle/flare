<?php

namespace Tests\Console\Flare;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class CreateAdminTest extends TestCase
{
    use CreateRole,
        CreateUser,
        RefreshDatabase;

    public function test_create_admin()
    {
        $this->createAdminRole();

        $this->artisan('create:admin test@gmail.com')->assertExitCode(0);
    }

    public function test_create_admin_user_exists()
    {
        $this->createAdminRole();

        $user = $this->createUser();

        $this->artisan('create:admin '.$user->email)->assertExitCode(0);
    }
}
