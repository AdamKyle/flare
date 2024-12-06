<?php

namespace Tests\Feature\Game\Messages\Controllers\Api;

use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\ChatMessageTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class ServerMessageControllerTest extends TestCase
{
    use RefreshDatabase;

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

    public function testGenerateServerMessageWithCustomMessage()
    {

        Event::fake();

        $character = $this->character->getCharacter();

        $message = 'This is a server message';

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/server-message', [
                '_token' => csrf_token(),
                'custom_message' => $message,
            ]);


        Event::assertDispatched(function (ServerMessageEvent $event) use ($message) {
            return $event->message === $message;
        });

        $this->assertEquals(200, $response->status());
    }

    public function testFailToGenerateServerMessage()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/server-message', [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        Event::assertNotDispatched(ServerMessageEvent::class);

        $this->assertEquals(422, $response->status());

        $this->assertEquals('Cannot generate server message for either type or custom message.', $jsonData['message']);
    }
}
