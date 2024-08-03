<?php

namespace Tests\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class FlagUsersForDeletionTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function testFlagsUsersForDeletion()
    {

        Mail::fake();

        $user = $this->createUser([
            'last_logged_in' => now()->subMonths(5),
        ]);

        $this->assertEquals(0, $this->artisan('flag:users-for-deletion'));

        $user = $user->refresh();

        $this->assertTrue($user->will_be_deleted);

    }

    public function testDoesNotFlagValidUsers()
    {

        Mail::fake();

        $user = $this->createUser([
            'last_logged_in' => now(),
        ]);

        $this->assertEquals(0, $this->artisan('flag:users-for-deletion'));

        $user = $user->refresh();

        $this->assertFalse($user->will_be_deleted);

    }
}
