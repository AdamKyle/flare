<?php

namespace Tests\Console\Admin;

use App\Admin\Mail\GeneratedAdmin;
use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Exception\RuntimeException;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class CreateAdminAccountTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_create_admin()
    {
        Mail::fake();

        $this->createAdminRole();

        $this->assertEquals(0, $this->artisan('create:admin sample@void.com'));

        Mail::assertSent(GeneratedAdmin::class);

        $this->assertCount(1, User::all());

        $this->assertTrue(User::first()->hasRole('Admin'));
    }

    public function test_fail_to_create_admin()
    {

        $this->expectException(RuntimeException::class);

        Mail::fake();

        $this->createAdminRole();

        $this->assertEquals(0, $this->artisan('create:admin'));

        Mail::assertNotSent(GeneratedAdmin::class);

        $this->assertCount(0, User::all());
    }

    public function test_fail_to_create_duplicate_admin()
    {

        $this->createAdmin($this->createAdminRole(), [
            'email' => 'sample@void.com',
        ]);

        Mail::fake();

        $this->createAdminRole();

        $this->assertEquals(0, $this->artisan('create:admin sample@void.com'));

        /**
         * There should only be one admin. Not two.
         */
        $this->assertCount(1, User::all());

        $this->assertTrue(User::first()->hasRole('Admin'));
    }
}
