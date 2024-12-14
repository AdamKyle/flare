<?php

namespace Tests\Unit\Game\Messages\Services;

use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Services\ServerMessage;
use App\Game\Messages\Types\ChatMessageTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ServerMessageTest extends TestCase
{
    use CreateItem, CreateMessage, CreateNpc, CreateRole, CreateUser, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?ServerMessage $serverMessage;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->serverMessage = resolve(ServerMessage::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->serverMessage = null;
    }

    public function testGenerateServerMessageForChattingTooMuch()
    {

        $user = $this->character->getUser();

        Auth::login($user);

        $this->serverMessage->generateServerMessage(ChatMessageTypes::CHATTING_TO_MUCH);

        $user = $user->refresh();

        $this->assertEquals(1, $user->message_throttle_count);
    }

    public function testGenerateServerMessageOfType()
    {

        $user = $this->character->getUser();

        Auth::login($user);

        Event::fake();

        $this->serverMessage->generateServerMessage(ChatMessageTypes::INVALID_MESSAGE_LENGTH);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'Your message cannot be empty.';
        });
    }

    public function testGenerateCustomServerMessage()
    {

        $user = $this->character->getUser();

        Auth::login($user);

        Event::fake();

        $this->serverMessage->generateServerMessageForCustomMessage('Test Custom Message');

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'Test Custom Message';
        });
    }
}
