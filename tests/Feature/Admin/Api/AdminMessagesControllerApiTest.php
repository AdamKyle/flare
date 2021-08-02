<?php

namespace Tests\Feature\Admin\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\User;
use Tests\TestCase;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Setup\Character\CharacterFactory;

class AdminMessagesControllerApiTest extends TestCase {

    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateMessage;

    /**
     * @var CharacterFactory $character
     */
    private $character;

    /**
     * @var User $admin
     */
    private $admin;

    public function setUp(): void {
        parent::setUp();

        $this->admin = $this->createAdmin($this->createAdminRole(), []);

        $this->character = (new CharacterFactory())->createBaseCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
        $this->admin     = null;
    }

    public function testCanGetAllMessages() {

        $this->createMessage($this->character->getUser());

        $response = $this->actingAs($this->admin)
                         ->json('GET', '/api/admin/chat-messages')
                         ->response;

        $content   = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content);
    }

    public function testCanBanUserForTime() {
        Mail::fake();

        $character = $this->character->getCharacter();

        $response = $this->actingAs($this->admin)
            ->json('POST', '/api/admin/ban-user', [
                'ban_for'     => 'one-day',
                'ban_message' => 'because',
                'user_id'     => $character->user->id
            ])
            ->response;

        $character = $character->refresh();

        $this->assertEquals(200, $response->status());
        $this->assertTrue($character->user->is_banned);
        $this->assertNotNull($character->user->unbanned_at);
        $this->assertNotNull($character->user->banned_reason);

        Mail::assertSent(GenericMail::class);
    }

    public function testCanBanUserForEver() {
        Mail::fake();

        $character = $this->character->getCharacter();

        $response = $this->actingAs($this->admin)
            ->json('POST', '/api/admin/ban-user', [
                'ban_for'     => 'perm',
                'ban_message' => 'because',
                'user_id'     => $character->user->id
            ])
            ->response;

        $character = $character->refresh();

        $this->assertEquals(200, $response->status());
        $this->assertTrue($character->user->is_banned);
        $this->assertNull($character->user->unbanned_at);
        $this->assertNotNull($character->user->banned_reason);

        Mail::assertSent(GenericMail::class);
    }

    public function testCannotBanUserMissingData() {
        Mail::fake();

        $character = $this->character->getCharacter();

        $response = $this->actingAs($this->admin)
            ->json('POST', '/api/admin/ban-user', [
                'ban_for'     => 'perm',
                'ban_message' => 'because',
            ])
            ->response;

        $content   = json_decode($response->content());

        $character = $character->refresh();

        $this->assertEquals('User is required', $content->errors->user_id[0]);
        $this->assertEquals(422, $response->status());
        $this->assertFalse($character->user->is_banned);
        $this->assertNull($character->user->unbanned_at);
        $this->assertNull($character->user->banned_reason);

        Mail::assertNothingSent();
    }

    public function testCannotBanUserWhenInvalidUnBanAt() {
        Mail::fake();

        $character = $this->character->getCharacter();

        $response = $this->actingAs($this->admin)
            ->json('POST', '/api/admin/ban-user', [
                'ban_for'     => 'apple-sauce',
                'ban_message' => 'because',
                'user_id'     => $character->user->id
            ])
            ->response;

        $content   = json_decode($response->content());

        $character = $character->refresh();

        $this->assertEquals('Invalid input for ban length.', $content->message);
        $this->assertEquals(200, $response->status());
        $this->assertFalse($character->user->is_banned);
        $this->assertNull($character->user->unbanned_at);
        $this->assertNull($character->user->banned_reason);

        Mail::assertNothingSent();
    }

    public function testCanSilenceUser() {
        $user = $this->character->getUser();

        $response = $this->actingAs($this->admin)
            ->json('POST', '/api/admin/silence-user', [
                'for'     => 10,
                'user_id' => $user->id,
            ])
            ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testCannotSilenceUser() {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($this->admin)
            ->json('POST', '/api/admin/silence-user', [
                'user_id' => $character->user->id,
            ])
            ->response;

        $character = $character->refresh();

        $content   = json_decode($response->content());

        $this->assertEquals('Length of time to silence is required.', $content->errors->for[0]);
        $this->assertEquals(422, $response->status());
        $this->assertFalse($character->user->is_silenced);
    }

    public function testForceNameChange() {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($this->admin)
            ->json('POST', '/api/admin/force-name-change/' . $character->user->id)
            ->response;

        $character = $character->refresh();

        $this->assertEquals(200, $response->status());
        $this->assertTrue($character->force_name_change);
    }
}
