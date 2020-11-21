<?php

namespace Tests\Feature;

use Cache;
use Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mail;
use Str;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use App\Admin\Mail\UnBanRequestMail;

class UnbanRequestControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateRace,
        CreateClass,
        CreateCharacter;

    public function testCanSeeUnBanRequestPage() {
        $this->visit('/login')->click('Banned Unfairly?')->see('Unban Request Process');
    }

    public function testCanSeeSecurityCheck() {
        $user = $this->createUserWithCharacter();

        $this->visit('/login')
             ->click('Banned Unfairly?')
             ->see('Unban Request Process')
             ->submitForm('Next Step', [
                 'email' => $user->email
             ])->see('Security Check');
    }

    public function testCannotSeeSecurityCheck() {
        $this->visit('/login')
             ->click('Banned Unfairly?')
             ->see('Unban Request Process')
             ->submitForm('Next Step', [
                 'email' => 'test@123.ca'
             ])->see('This email does not match our records.');
    }

    public function testCannotSeeSecurityCheckCacheExpired() {
        $user = $this->createUserWithCharacter();

        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('has')->andReturn(false);

        $this->visit('/login')
             ->click('Banned Unfairly?')
             ->see('Unban Request Process')
             ->submitForm('Next Step', [
                 'email' => $user->email
             ])->see('Invalid input. Please start the unban request process again.');
    }

    public function testUserSeesSecurityQuestionsResetAndLogin() {
        $user = $this->createUserWithCharacter();

        $password = Str::random(25);

        $this->visit(route('password.reset', app('Password')::getRepository()->create($user)))
             ->see('Reset Password')
             ->submitForm('Next Step', [
                'email' => $user->email,
                'password' => $password,
                'password_confirmation' => $password
             ])->submitForm('Reset and Login', [
                'question_one'          => 'Whats your favourite movie?',
                'question_two'          => 'Whats the name of the town you grew up in?', 
                'answer_one'            => 'test',
                'answer_two'            => 'test2',
             ])->see('Manage Kingdoms');
    }

    public function testCanSeeRequestForm() {
        $user = $this->createUserWithCharacter();

        $user->update([
            'is_banned' => true,
            'banned_reason' => 'Sample',
        ]);

        $this->visit('/login')
             ->click('Banned Unfairly?')
             ->see('Unban Request Process')
             ->submitForm('Next Step', [
                 'email' => $user->email
             ])
             ->submitForm('Next Step', [
                'question_one'          => 'test question',
                'question_two'          => 'test question 2', 
                'answer_one'            => 'test',
                'answer_two'            => 'test2',
             ])->see('Unban Request');
    }

    public function testCannotSeeRequestFormCacheFailed() {
        $user = $this->createUserWithCharacter();

        $user->update([
            'is_banned' => true,
            'banned_reason' => 'Sample',
        ]);

        Cache::shouldReceive('put')->andReturn(true);

        Cache::shouldReceive('has')->once()->andReturn(true);

        Cache::shouldReceive('has')->once()->andReturn(false);

        $this->visit('/login')
             ->click('Banned Unfairly?')
             ->see('Unban Request Process')
             ->submitForm('Next Step', [
                 'email' => $user->email
             ])
             ->submitForm('Next Step', [
                'question_one'          => 'test question',
                'question_two'          => 'test question 2', 
                'answer_one'            => 'test',
                'answer_two'            => 'test2',
             ])->see('Invalid input. Please start the unban request process again.');
    }

    public function testCannotSeeRequestFormAnswersDontMatch() {
        $user = $this->createUserWithCharacter();

        $user->update([
            'is_banned' => true,
            'banned_reason' => 'Sample',
        ]);

        $this->visit('/login')
             ->click('Banned Unfairly?')
             ->see('Unban Request Process')
             ->submitForm('Next Step', [
                 'email' => $user->email
             ])
             ->submitForm('Next Step', [
                'question_one'          => 'test question',
                'question_two'          => 'test question 2', 
                'answer_one'            => 'apples',
                'answer_two'            => 'bananas',
             ])->see('The answer to one or more security questions does not match our records.');
    }

    public function testCanSubmitRequest() {
        $user = $this->createUserWithCharacter();

        $admin = $this->createUser();

        $this->createAdminRole('Admin');
        $admin->assignRole('Admin');

        Mail::fake();

        $user->update([
            'is_banned' => true,
            'banned_reason' => 'Sample',
        ]);

        $this->visit('/login')
             ->click('Banned Unfairly?')
             ->see('Unban Request Process')
             ->submitForm('Next Step', [
                 'email' => $user->email
             ])
             ->submitForm('Next Step', [
                'question_one'          => 'test question',
                'question_two'          => 'test question 2', 
                'answer_one'            => 'test',
                'answer_two'            => 'test2',
             ])
             ->see('Unban Request')
             ->submitForm('Submit Request', [
                 'unban_message' => 'Sample'
             ])->see('Request submitted. We will contact you in the next 72 hours.');

        Mail::assertSent(UnBanRequestMail::class);
    }

    public function testCannotSubmitRequestCacheMiss() {
        $user = $this->createUserWithCharacter();

        $admin = $this->createUser();

        $this->createAdminRole('Admin');
        $admin->assignRole('Admin');

        Mail::fake();

        $user->update([
            'is_banned' => true,
            'banned_reason' => 'Sample',
        ]);

        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('delete')->andReturn(true);

        Cache::shouldReceive('has')->never();
        Cache::shouldReceive('has')->once()->andReturn(true);
        Cache::shouldReceive('has')->once()->andReturn(true);
        Cache::shouldReceive('has')->once()->andReturn(true);
        Cache::shouldReceive('has')->once()->andReturn(false);

        $this->visit('/login')
             ->click('Banned Unfairly?')
             ->see('Unban Request Process')
             ->submitForm('Next Step', [
                 'email' => $user->email
             ])
             ->submitForm('Next Step', [
                'question_one'          => 'test question',
                'question_two'          => 'test question 2', 
                'answer_one'            => 'test',
                'answer_two'            => 'test2',
             ])
             ->see('Unban Request')
             ->submitForm('Submit Request', [
                 'unban_message' => 'Sample'
             ])->see('Invalid input. Please start the unban request process again.');

        Mail::assertNotSent(UnBanRequestMail::class);
    }

    public function testCannotSubmitRequest() {
        $user = $this->createUserWithCharacter();

        $admin = $this->createUser();

        $this->createAdminRole('Admin');
        $admin->assignRole('Admin');

        Mail::fake();

        $user->update([
            'is_banned' => true,
            'banned_reason' => 'Sample',
            'un_ban_request' => 'reason here.'
        ]);

        $this->visit('/login')
             ->click('Banned Unfairly?')
             ->see('Unban Request Process')
             ->submitForm('Next Step', [
                 'email' => $user->email
             ])
             ->submitForm('Next Step', [
                'question_one'          => 'test question',
                'question_two'          => 'test question 2', 
                'answer_one'            => 'test',
                'answer_two'            => 'test2',
             ])
             ->see('Unban Request')
             ->submitForm('Submit Request', [
                 'unban_message' => 'Sample'
             ])->see('You already submitted a request. Future requests are ignored.');

        Mail::assertNotSent(UnBanRequestMail::class);
    }

    protected function createUserWithCharacter() {
        $user = $this->createUser([
            'is_banned' => false,
            'email'  => 'test@test.com',
        ]);

        $user->securityQuestions()->insert([
            [
                'user_id'  => $user->id,
                'question' => 'test question',
                'answer' => Hash::make('test'),
            ],
            [
                'user_id' => $user->id,
                'question' => 'test question 2',
                'answer' => Hash::make('test2'),
            ]
        ]);

        $this->createRace([
            'dex_mod' => 2,
        ]);

        $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $this->createCharacter([
            'name'    => 'Apples',
            'user_id' => $user->id,
        ]);

        return $user;
    }
}
