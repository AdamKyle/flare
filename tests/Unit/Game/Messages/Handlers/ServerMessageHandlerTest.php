<?php

namespace Tests\Unit\Game\Messages\Handlers;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
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

    public function testSendBasicMessageDoesNotThrowWhenBroadcastTransportFails(): void
    {
        $user = $this->createUser();

        Event::listen(ServerMessageEvent::class, function () {
            throw new BroadcastException('Pusher connection refused');
        });

        Log::shouldReceive('warning')->once();

        $this->serverMessageHandler->sendBasicMessage($user, 'test message');

        $this->assertTrue(true);
    }

    public function testSendBasicMessageLogsWarningWithContextWhenBroadcastTransportFails(): void
    {
        $user = $this->createUser();

        Event::listen(ServerMessageEvent::class, function () {
            throw new BroadcastException('Pusher connection refused');
        });

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($user) {
                return $message === 'Non-critical broadcast event failed.'
                    && str_contains($context['event_class'], 'ServerMessageEvent')
                    && str_contains($context['exception_class'], 'BroadcastException')
                    && $context['exception'] === 'Pusher connection refused'
                    && $context['user_id'] === $user->id;
            });

        $this->serverMessageHandler->sendBasicMessage($user, 'test message');
    }

    public function testSendBasicMessageDoesNotThrowOnNonBroadcastException(): void
    {
        $user = $this->createUser();

        Event::listen(ServerMessageEvent::class, function () {
            throw new \RuntimeException('Database connection lost');
        });

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($user) {
                return $message === 'Non-critical broadcast event failed.'
                    && str_contains($context['event_class'], 'ServerMessageEvent')
                    && str_contains($context['exception_class'], 'RuntimeException')
                    && $context['exception'] === 'Database connection lost'
                    && $context['user_id'] === $user->id;
            });

        $this->serverMessageHandler->sendBasicMessage($user, 'test message');

        $this->assertTrue(true);
    }
}
