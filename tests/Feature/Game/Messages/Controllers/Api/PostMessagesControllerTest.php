<?php

namespace Tests\Feature\Game\Messages\Controllers\Api;

use App\Game\Messages\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUserSession;

class PostMessagesControllerTest extends TestCase
{
    use CreateRole, CreateUser, CreateUserSession, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_post_public_message()
    {
        $character = $this->character->getCharacter();

        $message = 'Hello World, This is a public message';

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/public-message', [
                '_token' => csrf_token(),
                'message' => $message,
            ]);

        $this->assertEquals(200, $response->status());

        $message = Message::where('message', $message)->first();

        $this->assertNotNull($message);
    }

    public function test_post_private_message()
    {
        $this->createAdmin($this->createAdminRole());

        $character = $this->character->getCharacter();
        $secondaryCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createUserSession($secondaryCharacter->user);

        $message = 'Hello World, This is a private message';

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/private-message', [
                '_token' => csrf_token(),
                'message' => $message,
                'user_name' => $secondaryCharacter->name,
            ]);

        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->json('delivered'));

        $message = Message::where('message', $message)->first();

        $this->assertNotNull($message);

        $this->assertEquals($character->user_id, $message->from_user);
        $this->assertEquals($secondaryCharacter->user_id, $message->to_user);
    }

    public function test_post_private_message_returns_not_delivered_for_offline_character()
    {
        $character = $this->character->getCharacter();
        $offlineCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $message = 'Hello World, This message should not be delivered';

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/private-message', [
                '_token' => csrf_token(),
                'message' => $message,
                'user_name' => $offlineCharacter->name,
            ]);

        $this->assertEquals(200, $response->status());
        $this->assertFalse($response->json('delivered'));
        $this->assertSame(0, Message::where('message', $message)->count());
    }
}
