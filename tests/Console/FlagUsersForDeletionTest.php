<?php

namespace Tests\Console;


use App\Flare\Jobs\DailyGoldDustJob;
use App\Flare\Mail\GenericMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateUser;

class FlagUsersForDeletionTest extends TestCase
{
    use RefreshDatabase, CreateUser;

    public function testFlagsUsersForDeletion()
    {

        Mail::fake();

        $user = $this->createUser([
            'last_logged_in' => null,
        ]);

        $this->assertEquals(0, $this->artisan('flag:users-for-deletion'));

        Mail::assertSent(GenericMail::class);

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

        Mail::assertNotSent(GenericMail::class);

        $user = $user->refresh();

        $this->assertFalse($user->will_be_deleted);

    }
}
