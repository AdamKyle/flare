<?php

namespace Tests\Feature\Admin\Users;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Location;
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
}
