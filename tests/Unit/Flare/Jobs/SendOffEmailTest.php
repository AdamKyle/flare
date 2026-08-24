<?php

namespace Tests\Unit\Flare\Jobs;

use App\Flare\Jobs\SendOffEmail;
use App\Flare\Mail\GenericMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class SendOffEmailTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_dispatched_job_sends_the_mailable_to_the_user(): void
    {
        $user = $this->createUser();
        $mailable = new GenericMail($user, 'A message', 'A subject');

        Mail::fake();

        SendOffEmail::dispatch($user, $mailable);

        Mail::assertSent(GenericMail::class, fn ($mail) => $mail->hasTo($user->email));
    }
}
