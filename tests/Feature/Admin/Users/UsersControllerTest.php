<?php

namespace Tests\Feature\Admin\Users;

use Event;
use Mail;
use Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Admin\Mail\GenericMail;
use Tests\Setup\CharacterSetup;
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

        $this->user = $this->createAdmin([], $role);

        $this->character = (new CharacterSetup)->setupCharacter($this->createUser())
                                               ->setSkill('Looting')
                                               ->setSkill('Dodge')
                                               ->setSkill('Accuracy')
                                               ->getCharacter();
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
            'user' => $this->character->user->id
        ]))->response;

        $response->assertSessionHas('success', $this->character->name . ' password reset email sent.');
    }

    public function testCanSeeShowForNonAdmin() {

        $this->actingAs($this->user)->visit(route('users.user', [
            'user' => $this->character->user
        ]))->see($this->character->name);
    }

    public function testCantShowForAdmin() {

        $this->actingAs($this->user)->visit(route('skills.list'))->visit(route('users.user', [
            'user' => $this->user
        ]))->see('Admins do not have characters');
    }

    public function testCannotSilenceUser() {
        $response = $this->actingAs($this->user)->post(route('user.silence', [
            'user' => $this->character->user->id
        ]))->response;

        $this->assertFalse($this->user->refresh()->is_silenced);
    }

    public function testCanSilenceUser() {
        Queue::fake();
        Event::fake();
        
        $response = $this->actingAs($this->user)->post(route('user.silence', [
            'user' => $this->character->user->id
        ]), [
            'silence_for' => 10
        ])->response;

        $user = $this->character->refresh()->user;

        $this->assertTrue($user->is_silenced);

        $response->assertSessionHas('success', $this->character->name . ' Has been silenced for: ' . 10 . ' minutes');
    }

    public function testBanUserRedirects() {
        $response = $this->actingAs($this->user)->post(route('ban.user', [
            'user' => $this->character->user->id
        ]), [
            'ban_for' => 'one-day'
        ])->response;

        $this->assertEquals(302, $response->status());
    }

    public function testBanReasonForm() {
        $this->actingAs($this->user)->visit(route('ban.reason', [
            'user' => $this->character->user,
            'for'  => 'one-day',
        ]))->see('Reason For Ban');
    }

    public function testCanBanUserForOneDay() {
        Mail::Fake();
        
        $response = $this->actingAs($this->user)->post(route('ban.user.with.reason', [
            'user' => $this->character->user->id
        ]), [
            'for' => 'one-day',
            'reason' => 'sample reason',
        ])->response;

        $user = $this->character->refresh()->user;

        $this->assertTrue($user->is_banned);
        $this->assertNotNull($user->unbanned_at);
    }

    public function testCanBanUserForOneWeek() {
        Queue::fake();
        Event::fake();
        
        $response = $this->actingAs($this->user)->post(route('ban.user.with.reason', [
            'user' => $this->character->user->id
        ]), [
            'for' => 'one-week',
            'reason' => 'sample reason',
        ])->response;

        $user = $this->character->refresh()->user;

        $this->assertTrue($user->is_banned);
        $this->assertNotNull($user->unbanned_at);
    }

    public function testCanBanUserPerm() {

        $user = $this->character->user;
        
        $this->actingAs($this->user)->post(route('ban.user.with.reason', [
            'user' => $this->character->user->id
        ]), [
            'for' => 'perm',
            'reason' => 'sample reason',
        ])->response;

        $user = $user->refresh();

        $this->assertTrue($user->is_banned);
        $this->assertNull($user->unbanned_at);
    }

    public function testCannotBanUserUnknownLength() {
        Queue::fake();
        Event::fake();
        
        $response = $this->actingAs($this->user)->post(route('ban.user', [
            'user' => $this->character->user->id
        ]), [])->response;

        $user = $this->character->refresh()->user;

        $this->assertFalse($user->is_banned);
        $this->assertNull($user->unbanned_at);
    }

    public function testCanUnBanUser() {
        Queue::fake();
        Event::fake();

        $this->character->user()->update([
            'is_banned' => true,
            'unbanned_at' => null,
        ]);
        
        $response = $this->actingAs($this->user)->post(route('unban.user', [
            'user' => $this->character->user->id
        ]))->response;

        $user = $this->character->refresh()->user;

        $this->assertFalse($user->is_banned);
        $this->assertNull($user->unbanned_at);

        $response->assertSessionHas('success', 'User has been unbanned.');
    }

    public function testIgnoreUnBanRequest() {
        Mail::fake();

        $this->character->user()->update([
            'is_banned' => true,
            'un_ban_request' => 'Sample request.',
            'banned_reason'  => 'Sample reason.'
        ]);

        $this->character = $this->character->refresh();

        $response = $this->actingAs($this->user)->post(route('user.ignore.unban.request', [
            'user' => $this->character->user->id
        ]))->response;

        $response->assertSessionHas('success', 'User request to be unbanned was ignored. Email has been sent.');

        Mail::assertSent(GenericMail::class, 1);
    }

    public function testForceNameChange() {
        Event::fake();

        $response = $this->actingAs($this->user)->post(route('user.force.name.change', [
            'user' => $this->character->user->id
        ]))->response;

        $response->assertSessionHas('success', $this->character->name . ' forced to change their name.');
    }

    protected function createMultipleCharacters(int $amount = 1, string $ip): array {
        $characterIds = [];

        for($i = 1; $i <= $amount; $i++) {
            $characterIds[] = (new CharacterSetup)->setupCharacter($this->createUser(
                [
                    'ip_address' => $ip,
                ],
            ))
            ->setSkill('Looting')
            ->setSkill('Dodge')
            ->setSkill('Accuracy')
            ->getCharacter()->user->id;
        }

        return $characterIds;
    }
}
