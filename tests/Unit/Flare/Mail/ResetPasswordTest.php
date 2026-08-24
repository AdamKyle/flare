<?php

namespace Tests\Unit\Flare\Mail;

use App\Flare\Mail\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class ResetPasswordTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_build_sets_the_subject_view_and_token(): void
    {
        $user = $this->createUser();

        $mailable = new ResetPassword($user, 'a-token');
        $mailable->build();

        $this->assertSame('Password reset', $mailable->subject);
        $this->assertSame('flare.email.password_reset', $mailable->view);
        $this->assertSame(['token' => 'a-token'], $mailable->viewData);
    }
}
