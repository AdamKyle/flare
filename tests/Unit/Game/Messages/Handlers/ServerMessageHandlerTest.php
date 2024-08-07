<?php

namespace Tests\Unit\Game\Messages\Handlers;

use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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

        $this->serverMessageHandler->handleMessage($user, 'level_up', 1);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testSendBasicMessage()
    {
        $user = $this->createUser();

        Event::fake();

        $this->serverMessageHandler->sendBasicMessage($user, 'message');

        Event::assertDispatched(ServerMessageEvent::class);
    }
}
