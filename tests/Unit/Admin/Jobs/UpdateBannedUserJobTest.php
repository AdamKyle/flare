<?php

namespace Tests\Unit\Admin\Jobs;

use App\Admin\Jobs\UpdateBannedUserJob;
use App\Flare\Mail\GenericMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mail;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class UpdateBannedUserJobTest extends TestCase
{
    use RefreshDatabase, CreateUser;

    public function testUpdateSilencedJob()
    {
        $user = $this->createUser(
            [
                'is_banned' => true,
            ]
        );

        Mail::fake();

        UpdateBannedUserJob::dispatch($user);

        $user = $user->refresh();

        $this->assertFalse($user->is_banned);

        Mail::assertSent(function (GenericMail $mail) {
            return $mail->genericMessage === 'You are now unbanned and may log in again.';
        }, 1);
    }

    
}
