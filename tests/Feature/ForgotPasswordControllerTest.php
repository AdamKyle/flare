<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mail;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ForgotPasswordControllerTest extends TestCase
{
    use CreateRole,
        CreateUser,
        RefreshDatabase;

    public function test_can_see_reset_password_page()
    {

        $this->visit('/login')
            ->click('Forgot Your Password?')
            ->see('Reset Password');
    }

    public function test_admin_can_reset_no_security_questions()
    {

        Mail::fake();

        $user = $this->createUserWithCharacter()->getUser();

        $this->createAdminRole('Admin');
        $user->assignRole('Admin');

        $this->visit('/login')
            ->click('Forgot Your Password?')
            ->see('Reset Password')
            ->submitForm('Next Step', [
                'email' => $user->email,
            ])->see('Sent you an email to begin the reset process.');
    }

    public function test_user_goes_through_security_questions()
    {

        $user = $this->createUserWithCharacter()->getUser();

        $this->visit('/login')
            ->click('Forgot Your Password?')
            ->see('Reset Password')
            ->submitForm('Next Step', [
                'email' => $user->email,
            ])->see('Sent you an email to begin the reset process.');
    }

    public function test_fail_with_unknown_email()
    {

        $this->visit('/login')
            ->click('Forgot Your Password?')
            ->see('Reset Password')
            ->submitForm('Next Step', [
                'email' => 'a@t.ca',
            ])->see('This email does not match our records.');
    }

    protected function createUserWithCharacter()
    {
        return (new CharacterFactory)->createBaseCharacter();
    }
}
