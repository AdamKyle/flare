<?php

namespace Tests\Feature\Admin\Mail;

use App\Admin\Mail\ResetPasswordEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mail;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;

class PasswordResetMailTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole;

    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin([], $role);

        Mail::fake();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user  = null;
    }

    public function testSendPasswordReset() {
        Mail::assertNothingSent();

        $this->actingAs($this->user)->post(route('user.reset.password', [
            'user' => $this->user->id
        ]));

        Mail::assertSent(function (ResetPasswordEmail $mail) {
            return $mail->user->id === $this->user->id && $mail->hasTo($this->user->email);
        });
    } 
}
