<?php

namespace Tests\Unit\Flare\Mail;

use App\Flare\Mail\GenericMail;
use App\Flare\Mail\MailHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class MailHandlerTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_build_sends_the_wrapped_mailable_to_the_given_email(): void
    {
        $user = $this->createUser();
        $mailable = new GenericMail($user, 'A message', 'A subject');

        Mail::fake();

        (new MailHandler($user->email, $mailable))->build();

        Mail::assertSent(GenericMail::class, fn ($mail) => $mail->hasTo($user->email));
    }
}
