<?php

namespace Tests\Unit\Game\Messages\Services;


use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use App\Game\Messages\Services\ServerMessage;
use App\Game\Messages\Events\ServerMessageEvent;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ServerMessageTest extends TestCase {

    use RefreshDatabase, CreateMessage, CreateUser, CreateRole, CreateNpc, CreateItem;

    private ?CharacterFactory $character;

    private ?ServerMessage $serverMessage;

    public function setUp(): void {
        parent::setUp();

        $this->character     = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->serverMessage = resolve(ServerMessage::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character     = null;
        $this->serverMessage = null;
    }

    public function testGenerateServerMessageForChattingTooMuch() {

        $user = $this->character->getUser();

        Auth::login($user);

        $this->serverMessage->generateServerMessage('chatting_to_much');

        $user = $user->refresh();

        $this->assertEquals(1, $user->message_throttle_count);
    }

    public function testGenerateServerMessageOfType() {

        $user = $this->character->getUser();

        Auth::login($user);

        Event::fake();

        $this->serverMessage->generateServerMessage('message_length_0');

        Event::assertDispatched(function(ServerMessageEvent $event) {
            return $event->message === 'Your message cannot be empty.';
        });
    }

    public function testGenerateCustomServerMessage() {

        $user = $this->character->getUser();

        Auth::login($user);

        Event::fake();

        $this->serverMessage->generateServerMessageForCustomMessage('Test Custom Message');

        Event::assertDispatched(function(ServerMessageEvent $event) {
            return $event->message === 'Test Custom Message';
        });
    }

}
