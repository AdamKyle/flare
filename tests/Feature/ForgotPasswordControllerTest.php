<?php

namespace Tests\Feature;

use App\Flare\Mail\ResetPassword;
use Cache;
use Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mail;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Setup\Character\CharacterFactory;

class ForgotPasswordControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateRace,
        CreateClass,
        CreateCharacter;

    public function testCanSeeResetPaswordPage() {

        $this->visit('/login')
             ->click('Forgot Your Password?')
             ->see('Reset Password');
    }

    public function testAdminCanResetNoSecurityQuestions() {

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

    public function testUserGoesThroughSecurityQuestions() {

        $user = $this->createUserWithCharacter()->getUser();

        $this->visit('/login')
             ->click('Forgot Your Password?')
             ->see('Reset Password')
             ->submitForm('Next Step', [
                 'email' => $user->email,
             ])->see('Security Questions');
    }

    public function testUserGoesThroughSecurityQuestionsCacheTimeout() {
        $user = $this->createUserWithCharacter()->getUser();

        Mail::fake();

        Cache::shouldReceive('put')->andReturn(true);

        Cache::shouldReceive('has')->once()->with($user->id . '-email')->andReturn(null);

        $this->visit('/login')
             ->click('Forgot Your Password?')
             ->see('Reset Password')
             ->submitForm('Next Step', [
                 'email' => $user->email,
             ])->see('Your time expired. Please try again.');
    }

    public function testFailWithUnknownEmail() {

        $this->visit('/login')
             ->click('Forgot Your Password?')
             ->see('Reset Password')
             ->submitForm('Next Step', [
                 'email' => 'a@t.ca',
             ])->see('This email does not match our records.');
    }

    public function testFunctionAnswerSecurityQuestions() {
        $user = $this->createUserWithCharacter()->getUser();

        $this->visit('/login')
             ->click('Forgot Your Password?')
             ->see('Reset Password')
             ->submitForm('Next Step', [
                 'email' => $user->email,
             ])
             ->submitForm('Finish', [
                'answer_one' => 'test',
                'answer_two' => 'test2',
                'question_one' => 'test question',
                'question_two' => 'test question 2',
             ])->see('Sent you an email to begin the reset process.');
    }

    public function testFunctionAnswerSecurityQuestionsAnswersDontMatch() {
        $user = $this->createUserWithCharacter()->getUser();

        Mail::fake();

        Cache::put($user->id . '-email', $user->email, now()->addMinutes(99));

        $this->visit(route('user.security.questions', [
            'user' => $user
        ]))->submitForm('Finish', [
                'answer_one' => '',
                'answer_two' => '',
                'question_one' => 'test question',
                'question_two' => 'test question 2',
             ])->see('The answer to one or more security questions does not match our records.');
    }

    public function testFunctionAnswerSecurityQuestionsAnswersCacheTimeOut() {
        $user = $this->createUserWithCharacter()->getUser();

        Mail::fake();

        Cache::shouldReceive('put')->andReturn(true);

        Cache::shouldReceive('has')->once()->with($user->id . '-email')->andReturn($user->email);

        Cache::shouldReceive('has')->once()->with($user->id . '-email')->andReturn(null);

        $this->visit('/login')
             ->click('Forgot Your Password?')
             ->see('Reset Password')
             ->submitForm('Next Step', [
                 'email' => $user->email,
             ])
             ->submitForm('Finish', [
                'answer_one' => '',
                'answer_two' => '',
                'question_one' => 'test question',
                'question_two' => 'test question 2',
             ])->see('Your time expired. Please try again.');
    }

    protected function createUserWithCharacter() {
        return (new CharacterFactory)->createBaseCharacter();
    }
}
