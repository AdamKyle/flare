<?php

namespace Tests\Feature\Game\Messages\Controllers\Api;

use App\Game\Messages\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class PostMessagesControllerTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateRole;

    private ?CharacterFactory $character = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testPostPublicMessage()
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

    public function testPostPrivateMessage()
    {

        $this->createAdmin($this->createAdminRole());

        $character = $this->character->getCharacter();
        $secondaryCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $message = 'Hello World, This is a private message';

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/private-message', [
                '_token' => csrf_token(),
                'message' => $message,
                'user_name' => $secondaryCharacter->name,
            ]);

        $this->assertEquals(200, $response->status());

        $message = Message::where('message', $message)->first();

        $this->assertNotNull($message);

        $this->assertEquals($character->user_id, $message->from_user);
        $this->assertEquals($secondaryCharacter->user_id, $message->to_user);
    }
}
