<?php

namespace Tests\Feature;

use Cache;
use Mail;
use Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Admin\Mail\UnBanRequestMail;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Setup\Character\CharacterFactory;

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
        $user = $this->createUserWithCharacter()->getUser();

        $user->update([
            'is_banned' => true,
            'unbanned_at' => null,
        ]);

        $this->visit('/login')
             ->click('Banned Unfairly?')
             ->see('Unban Request Process')
             ->submitForm('Next Step', [
                 'email' => $user->email
             ])->see('Security Check');
    }

    public function testCannotAccessUnBannedRequestBecauseNotBanned() {
        $user = $this->createUserWithCharacter()->getUser();

        $this->visit('/login')
            ->click('Banned Unfairly?')
            ->see('Unban Request Process')
            ->submitForm('Next Step', [
                'email' => $user->email
            ])->see('You are not banned.');
    }

    public function testCannotAccessUnBannedRequestBecauseNotBannedForEver() {
        $user = $this->createUserWithCharacter()->getUser();

        $user->update([
            'is_banned' => true,
            'unbanned_at' => now()->addDays(1),
        ]);

        $this->visit('/login')
            ->click('Banned Unfairly?')
            ->see('Unban Request Process')
            ->submitForm('Next Step', [
                'email' => $user->email
            ])
            ->see('You are not banned forever.');
    }

    public function testCannotSeeSecurityCheckEmailDoesNotExist() {
        $this->visit('/login')
             ->click('Banned Unfairly?')
             ->see('Unban Request Process')
             ->submitForm('Next Step', [
                 'email' => 'test@123.ca'
             ])->see('This email does not match our records.');
    }

    public function testCannotSeeSecurityCheckCacheExpired() {
        $user = $this->createUserWithCharacter()->getUser();

        $user->update([
            'is_banned' => true,
            'unbanned_at' => null,
        ]);

        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('has')->andReturn(false);

        $this->visit('/login')
             ->click('Banned Unfairly?')
             ->see('Unban Request Process')
             ->submitForm('Next Step', [
                 'email' => $user->email
             ])->see('Invalid input. Please start the unban request process again.');
    }

    public function testCanSeeRequestForm() {
        $user = $this->createUserWithCharacter()->banCharacter('Sample')->getUser();

        $user->update([
            'is_banned' => true,
            'unbanned_at' => null,
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
        $user = $this->createUserWithCharacter()->banCharacter('sample')->getUser();

        $user->update([
            'is_banned' => true,
            'unbanned_at' => null,
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
        $user = $this->createUserWithCharacter()->banCharacter('Sample')->getUser();

        $user->update([
            'is_banned' => true,
            'unbanned_at' => null,
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
        $user = $this->createUserWithCharacter()->banCharacter('Sample')->getUser();

        $user->update([
            'is_banned' => true,
            'unbanned_at' => null,
        ]);

        $admin = $this->createUser();

        $this->createAdminRole('Admin');
        $admin->assignRole('Admin');

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
    }

    public function testCannotSubmitRequestCacheMiss() {
        $user = $this->createUserWithCharacter()->banCharacter('Sample')->getUser();

        $user->update([
            'is_banned' => true,
            'unbanned_at' => null,
        ]);

        $admin = $this->createUser();

        $this->createAdminRole('Admin');
        $admin->assignRole('Admin');

        Mail::fake();

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

    public function testCannotSubmitRequestBecauseRequestWasAlreadySubmitted() {
        $user = $this->createUserWithCharacter()->banCharacter('Sample', 'reason here.')->getUser();

        $user->update([
            'is_banned' => true,
            'unbanned_at' => null,
        ]);

        $admin = $this->createUser();

        $this->createAdminRole('Admin');
        $admin->assignRole('Admin');

        Mail::fake();

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
        return (new CharacterFactory)->createBaseCharacter();
    }
}
