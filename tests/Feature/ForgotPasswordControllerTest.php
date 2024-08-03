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

    public function testCanSeeResetPasswordPage()
    {

        $this->visit('/login')
            ->click('Forgot Your Password?')
            ->see('Reset Password');
    }

    public function testAdminCanResetNoSecurityQuestions()
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

    public function testUserGoesThroughSecurityQuestions()
    {

        $user = $this->createUserWithCharacter()->getUser();

        $this->visit('/login')
            ->click('Forgot Your Password?')
            ->see('Reset Password')
            ->submitForm('Next Step', [
                'email' => $user->email,
            ])->see('Sent you an email to begin the reset process.');
    }

    public function testFailWithUnknownEmail()
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
