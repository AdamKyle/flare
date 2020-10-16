<?php

namespace Tests\Feature\Admin\Users;

use App\Admin\Mail\ResetPasswordEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Location;
use App\Flare\Models\User;
use Event;
use Mail;
use Queue;
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

        $response->assertSessionHas('error', 'Invalid input.');
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

    public function testCanBanUserForOneDay() {
        Queue::fake();
        Event::fake();
        
        $response = $this->actingAs($this->user)->post(route('ban.user', [
            'user' => $this->character->user->id
        ]), [
            'ban_for' => 'one-day'
        ])->response;

        $user = $this->character->refresh()->user;

        $this->assertTrue($user->is_banned);
        $this->assertNotNull($user->unbanned_at);

        $response->assertSessionHas('success', 'User has been banned.');
    }

    public function testCanBanUserForOneWeek() {
        Queue::fake();
        Event::fake();
        
        $response = $this->actingAs($this->user)->post(route('ban.user', [
            'user' => $this->character->user->id
        ]), [
            'ban_for' => 'one-week'
        ])->response;

        $user = $this->character->refresh()->user;

        $this->assertTrue($user->is_banned);
        $this->assertNotNull($user->unbanned_at);

        $response->assertSessionHas('success', 'User has been banned.');
    }

    public function testCanBanUserPerm() {
        Queue::fake();
        Event::fake();

        $user = $this->character->user;
        
        $response = $this->actingAs($this->user)->post(route('ban.user', [
            'user' => $user->id
        ]), [
            'ban_for' => 'perm'
        ])->response;

        $user = $user->refresh();

        $this->assertTrue($user->is_banned);
        $this->assertNull($user->unbanned_at);

        $response->assertSessionHas('success', 'User has been banned.');
    }

    public function testCanBanUserAllCharactersAreBanned() {
        Queue::fake();
        Event::fake();

        $userIds = $this->createMultipleCharacters(10, '134.0.0.1');

        $user = User::find($userIds[rand(0, (count($userIds) - 1))]);
        
        $response = $this->actingAs($this->user)->post(route('ban.user', [
            'user' => $user->id
        ]), [
            'ban_for' => 'perm'
        ])->response;

        $user = $user->refresh();

        $this->assertTrue($user->is_banned);
        $this->assertNull($user->unbanned_at);

        $response->assertSessionHas('success', 'User has been banned.');

        foreach($userIds as $id) {
            $user = User::find($id);
            
            $this->assertTrue($user->is_banned);
            $this->assertNull($user->unbanned_at);
        }
    }

    protected function createMultipleCharacters(int $amount = 1, string $ip): array {
        $characterIds = [];

        for($i = 1; $i <= $amount; $i++) {
            $characterIds[] = (new CharacterSetup)->setupCharacter($this->createUser(
                ['ip_address' => $ip],
            ))
            ->setSkill('Looting')
            ->setSkill('Dodge')
            ->setSkill('Accuracy')
            ->getCharacter()->user->id;
        }

        return $characterIds;
    }

    public function testCannotBanUser() {
        Queue::fake();
        Event::fake();
        
        $response = $this->actingAs($this->user)->post(route('ban.user', [
            'user' => $this->character->user->id
        ]))->response;

        $user = $this->character->refresh()->user;

        $this->assertFalse($user->is_banned);
        $this->assertNull($user->unbanned_at);

        $response->assertSessionHas('error', 'Invalid input.');
    }

    public function testCannotBanUserUnknownLength() {
        Queue::fake();
        Event::fake();
        
        $response = $this->actingAs($this->user)->post(route('ban.user', [
            'user' => $this->character->user->id
        ]), [
            'ban_for' => 'test'
        ])->response;

        $user = $this->character->refresh()->user;

        $this->assertFalse($user->is_banned);
        $this->assertNull($user->unbanned_at);

        $response->assertSessionHas('error', 'Invalid input for ban length.');
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
}
