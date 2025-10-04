<?php

namespace Tests\Feature\Game\Messages\Controllers\Api;

use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\MapMessageTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class ServerMessageControllerTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_generate_server_message_with_custom_message()
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

    public function test_fail_to_generate_server_message()
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

    public function test_generate_server_message_with_type()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/server-message', [
                '_token' => csrf_token(),
                'type' => MapMessageTypes::CANNOT_MOVE_DOWN->getValue(),
            ]);

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertEquals(200, $response->status());
    }

    public function test_fail_to_generate_server_message_for_invalid_type()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/server-message', [
                '_token' => csrf_token(),
                'type' => 'skjfhf',
            ]);

        $jsonData = json_decode($response->getContent(), true);

        Event::assertNotDispatched(ServerMessageEvent::class);

        $this->assertEquals(422, $response->status());

        $this->assertEquals('Invalid message type was passed when trying to generate server message', $jsonData['message']);
    }
}
