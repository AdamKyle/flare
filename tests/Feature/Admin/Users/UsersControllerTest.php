<?php

namespace Tests\Feature\Admin\Users;

use Event;
use Mail;
use Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Mail\GenericMail;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;

class UsersControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole;

    private $user;

    protected $character;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);

        $this->character = (new CharacterFactory)->createBaseCharacter();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user      = null;
        $this->character = null;
    }

    public function testCanSeeIndexPage() {
        $this->actingAs($this->user)->visit(route('users.list'))->see('Users');
    }

    public function testCanResetPassword() {
        $response = $this->actingAs($this->user)->post(route('user.reset.password', [
            'user' => $this->character->getUser()->id
        ]))->response;

        $response->assertSessionHas('success', $this->character->getCharacter()->name . ' password reset email sent.');
    }

    public function testCanSeeShowForNonAdmin() {

        $this->actingAs($this->user)->visit(route('users.user', [
            'user' => $this->character->getUser()
        ]))->see($this->character->getCharacter()->name);
    }

    public function testCantShowForAdmin() {

        $this->actingAs($this->user)->visit(route('skills.list'))->visit(route('users.user', [
            'user' => $this->user
        ]))->see('Admins do not have characters');
    }

    public function testCannotSilenceUser() {

        $user = $this->character->getUser();

        $this->actingAs($this->user)->post(route('user.silence', [
            'user' => $user->id
        ]))->response;

        $this->assertFalse($this->user->refresh()->is_silenced);
        $this->assertFalse($user->refresh()->is_silenced);
    }

    public function testCanSilenceUser() {
        Queue::fake();
        Event::fake();

        $user = $this->character->getUser();

        $response = $this->actingAs($this->user)->post(route('user.silence', [
            'user' => $user->id
        ]), [
            'silence_for' => 10
        ])->response;

        $user = $user->refresh();

        $this->assertTrue($user->is_silenced);

        $response->assertSessionHas('success', $this->character->getCharacter()->name . ' Has been silenced for: ' . 10 . ' minutes');
    }

    public function testBanUserRedirects() {
        $response = $this->actingAs($this->user)->post(route('ban.user', [
            'user' => $this->character->getUser()->id
        ]), [
            'ban_for' => 'one-day'
        ])->response;

        $this->assertEquals(302, $response->status());
    }

    public function testBanReasonForm() {
        $this->actingAs($this->user)->visit(route('ban.reason', [
            'user' => $this->character->getUser(),
            'for'  => 'one-day',
        ]))->see('Reason For Ban');
    }

    public function testCanBanUserForOneDay() {
        Mail::Fake();

        $user = $this->character->getUser();

        $this->actingAs($this->user)->post(route('ban.user.with.reason', [
            'user' => $user->id
        ]), [
            'for' => 'one-day',
            'reason' => 'sample reason',
        ])->response;

        $user = $user->refresh();

        $this->assertTrue($user->is_banned);
        $this->assertNotNull($user->unbanned_at);
    }

    public function testCanBanUserForOneWeek() {
        Queue::fake();
        Event::fake();

        $user = $this->character->getUser();

        $this->actingAs($this->user)->post(route('ban.user.with.reason', [
            'user' => $user->id
        ]), [
            'for' => 'one-week',
            'reason' => 'sample reason',
        ])->response;

        $user = $user->refresh();

        $this->assertTrue($user->is_banned);
        $this->assertNotNull($user->unbanned_at);
    }

    public function testCanBanUserPerm() {

        Mail::fake();

        $user = $this->character->getUser();

        $response = $this->actingAs($this->user)->post(route('ban.user.with.reason', [
            'user' => $this->character->getUser()->id
        ]), [
            'for' => 'perm',
            'reason' => 'sample reason',
        ])->response;

        $user = $user->refresh();

        $this->assertTrue($user->is_banned);
        $this->assertNull($user->unbanned_at);

        Mail::assertSent(GenericMail::class);
    }

    public function testCannotBanUserUnknownLength() {
        Queue::fake();
        Event::fake();

        $response = $this->actingAs($this->user)->post(route('ban.user', [
            'user' => $this->character->getUser()->id
        ]), [])->response;

        $user = $this->character->getUser();

        $this->assertFalse($user->is_banned);
        $this->assertNull($user->unbanned_at);
    }

    public function testCanUnBanUser() {
        Queue::fake();
        Event::fake();

        $character = $this->character->banCharacter();
        $user      = $character->getUser();

        $response = $this->actingAs($this->user)->post(route('unban.user', [
            'user' => $user->id
        ]))->response;

        $user = $user->refresh();

        $this->assertFalse($user->is_banned);
        $this->assertNull($user->unbanned_at);

        $response->assertSessionHas('success', 'User has been unbanned.');
    }

    public function testIgnoreUnBanRequest() {
        Mail::fake();

        $character = $this->character->banCharacter('Sample', 'Sample');

        $response = $this->actingAs($this->user)->post(route('user.ignore.unban.request', [
            'user' => $character->getUser()->id
        ]))->response;

        $response->assertSessionHas('success', 'User request to be unbanned was ignored. Email has been sent.');

        Mail::assertSent(GenericMail::class, 1);
    }

    public function testForceNameChange() {
        Event::fake();

        $response = $this->actingAs($this->user)->post(route('user.force.name.change', [
            'user' => $this->character->getUser()->id
        ]))->response;

        $response->assertSessionHas('success', $this->character->getCharacter()->name . ' forced to change their name.');
    }
}
