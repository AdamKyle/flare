<?php

namespace Tests\Unit\Flare\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class CreateAdminTests extends TestCase
{

    use RefreshDatabase,
        CreateRole,
        CreateUser;

    public function testCreateAdmin() {
        $this->createAdminRole();

        $this->assertEquals(0, $this->artisan('create:admin test@gmail.com'));
    }

    public function testCreateAdminMissingEmail() {
        $this->createAdminRole();

        $this->assertEquals(0, $this->artisan('create:admin', ['email' => null]));
    }

    public function testCreateAdminUserExists() {
        $this->createAdminRole();

        $user = $this->createUser();

        $this->assertEquals(0, $this->artisan('create:admin ' . $user->email));
    }

}
