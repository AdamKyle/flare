<?php

namespace Tests\Feature;

use Cache;
use Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mail;
use Mockery;
use Str;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;

class ResetPasswordControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateRace,
        CreateClass,
        CreateCharacter;

    public function testCanSeeResetPaswordPage() {
        $user = $this->createUser();

        $this->createAdminRole('Admin');
        $user->assignRole('Admin');

        $this->visit(route('password.reset', app('Password')::getRepository()->create($user)))
             ->see('Reset Password');
    }

    public function testAdminDoesNotHaveSecurityQuestions() {
        $user = $this->createUser();

        $this->createAdminRole('Admin');
        $user->assignRole('Admin');

        $password = Str::random(25);

        $this->visit(route('password.reset', app('Password')::getRepository()->create($user)))
             ->see('Reset Password')
             ->submitForm('Next Step', [
                 'email' => $user->email,
                 'password' => $password,
                 'password_confirmation' => $password
             ])->see('Admin');
    }

    public function testUserSeesSecurityQuestions() {
        $user = $this->createUserWithCharacter();

        $password = Str::random(25);

        $this->visit(route('password.reset', app('Password')::getRepository()->create($user)))
             ->see('Reset Password')
             ->submitForm('Next Step', [
                 'email' => $user->email,
                 'password' => $password,
                 'password_confirmation' => $password
             ])->see('New Security Questions');
    }

    public function testUserSeesSecurityQuestionsEmailDoesntMatch() {
        $user = $this->createUserWithCharacter();

        $password = Str::random(25);

        $this->visit(route('password.reset', app('Password')::getRepository()->create($user)))
             ->see('Reset Password')
             ->submitForm('Next Step', [
                 'email' => '1111@test.ca',
                 'password' => $password,
                 'password_confirmation' => $password,
             ])->see('This email does not match our records.');
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

    public function testUserSeesSecurityQuestionsCantResetQuestionsAreTheSame() {
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
                'question_two'          => 'Whats your favourite movie?', 
                'answer_one'            => 'test',
                'answer_two'            => 'test2',
             ])->see('Security questions need to be unique.');
    }

    public function testUserSeesSecurityQuestionsCantResetAnswersAreTheSame() {
        $user = $this->createUserWithCharacter();

        $password = Str::random(25);

        $this->visit(route('password.reset', app('Password')::getRepository()->create($user)))
             ->see('Reset Password')
             ->submitForm('Next Step', [
                'email' => $user->email,
                'password' => $password,
                'password_confirmation' => $password
             ])->submitForm('Reset and Login', [
                'question_one'          => 'Whats the name of the town you grew up in?',
                'question_two'          => 'Whats your favourite movie?', 
                'answer_one'            => 'test',
                'answer_two'            => 'test',
             ])->see('Security questions answers need to be unique.');
    }

    public function testUserSeesSecurityQuestionsCantResetCacheTimeOut() {
        $user = $this->createUserWithCharacter();

        $password = Str::random(25);

        Cache::shouldReceive('put')->andReturn(true);

        Cache::shouldReceive('has')->andReturn(false);

        $this->visit(route('password.reset', app('Password')::getRepository()->create($user)))
             ->see('Reset Password')
             ->submitForm('Next Step', [
                'email' => $user->email,
                'password' => $password,
                'password_confirmation' => $password
             ])->submitForm('Reset and Login', [
                'question_one'          => 'Whats the name of the town you grew up in?',
                'question_two'          => 'Whats your favourite movie?', 
                'answer_one'            => 'test3',
                'answer_two'            => 'test',
             ])->see('Unable to process password reset. Please start again by following the forgot password link on the login page.');
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
