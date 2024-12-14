<?php

namespace Tests\Unit\Game\Messages\Handlers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Handlers\ServerMessageHandler;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Messages\Types\CurrenciesMessageTypes;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class ServerMessageHandlerTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    private ?ServerMessageHandler $serverMessageHandler;

    public function setUp(): void
    {
        parent::setUp();

        $this->serverMessageHandler = resolve(ServerMessageHandler::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->serverMessageHandler = null;
    }

    public function testHandleMessage()
    {
        $user = $this->createUser();

        Event::fake();

        $this->serverMessageHandler->handleMessage($user, CharacterMessageTypes::LEVEL_UP, 1);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testSendBasicMessage()
    {
        $user = $this->createUser();

        Event::fake();

        $this->serverMessageHandler->sendBasicMessage($user, 'message');

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testHandleMessageWithNewValue()
    {
        $user = $this->createUser();

        Event::fake();

        $this->serverMessageHandler->handleMessageWithNewValue($user, CurrenciesMessageTypes::GOLD, 200, 500);

        Event::assertDispatched(ServerMessageEvent::class);
    }
}
