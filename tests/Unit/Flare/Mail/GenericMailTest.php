<?php

namespace Tests\Unit\Flare\Mail;

use App\Flare\Mail\GenericMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class GenericMailTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_build_sets_the_subject_and_view(): void
    {
        $user = $this->createUser();

        $mailable = new GenericMail($user, 'A message', 'A subject');
        $mailable->build();

        $this->assertSame('A subject', $mailable->subject);
        $this->assertSame('flare.email.generic-email', $mailable->view);
    }
}
