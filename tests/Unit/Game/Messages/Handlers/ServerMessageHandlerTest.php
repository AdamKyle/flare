<?php

namespace Tests\Unit\Game\Messages\Handlers;

use App\Flare\Models\CharacterBattleRewardRequestMessage;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageContext;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageOutboxService;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Handlers\ServerMessageHandler;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Messages\Types\CurrenciesMessageTypes;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBattleReward;
use Tests\Traits\CreateUser;

class ServerMessageHandlerTest extends TestCase
{
    use CreateCharacterBattleReward, CreateUser, RefreshDatabase;

    private ?ServerMessageHandler $serverMessageHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverMessageHandler = new ServerMessageHandler(
            new ServerMessageBuilder,
            new BattleRewardMessageContext,
            new BattleRewardMessageOutboxService,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->serverMessageHandler = null;
    }

    public function test_handle_message()
    {
        $user = $this->createUser();

        Event::fake();

        $this->serverMessageHandler->handleMessage($user, CharacterMessageTypes::LEVEL_UP, 1);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_send_basic_message()
    {
        $user = $this->createUser();

        Event::fake();

        $this->serverMessageHandler->sendBasicMessage($user, 'message');

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_handle_message_with_new_value()
    {
        $user = $this->createUser();

        Event::fake();

        $this->serverMessageHandler->handleMessageWithNewValue($user, CurrenciesMessageTypes::GOLD, 200, 500);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_send_basic_message_inside_a_reward_context_stores_an_unemitted_outbox_message_without_broadcasting(): void
    {
        Event::fake();

        $request = $this->createCharacterBattleRewardRequest();
        $character = $request->character;
        $battleRewardMessageContext = new BattleRewardMessageContext;
        $battleRewardMessageContext->start($request->id, $character->id, $character->user_id);
        $battleRewardMessageContext->setStep(BattleRewardStepName::XP);

        $serverMessageHandler = new ServerMessageHandler(
            new ServerMessageBuilder,
            $battleRewardMessageContext,
            new BattleRewardMessageOutboxService,
        );

        $serverMessageHandler->sendBasicMessage($character->user, 'reward message');

        $storedMessage = CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)->sole();

        $this->assertSame('reward message', $storedMessage->message);
        $this->assertSame(BattleRewardStepName::XP, $storedMessage->step_name);
        $this->assertNull($storedMessage->emitted_at);
        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function test_send_basic_message_does_not_throw_when_broadcast_transport_fails(): void
    {
        $user = $this->createUser();

        Event::listen(ServerMessageEvent::class, function () {
            throw new BroadcastException('Pusher connection refused');
        });

        Log::shouldReceive('warning')->once();

        $result = $this->serverMessageHandler->sendBasicMessage($user, 'test message');

        $this->assertNull($result);
    }

    public function test_send_basic_message_logs_warning_with_context_when_broadcast_transport_fails(): void
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

        $result = $this->serverMessageHandler->sendBasicMessage($user, 'test message');

        $this->assertNull($result);
    }

    public function test_send_basic_message_logs_warning_with_context_when_non_broadcast_exception_occurs(): void
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

        $result = $this->serverMessageHandler->sendBasicMessage($user, 'test message');

        $this->assertNull($result);
    }
}
