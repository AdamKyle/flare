<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use Tests\Traits\CreateCharacterBattleReward;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestMessage;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageOutboxService;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageContext;
use App\Game\Messages\Handlers\ServerMessageHandler;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class BattleRewardMessageOutboxServiceTest extends TestCase
{
    use CreateCharacterBattleReward, RefreshDatabase;

    public function testStoreMessageCreatesRecord(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);

        resolve(BattleRewardMessageOutboxService::class)->storeMessage(
            $request->id,
            $character->id,
            $character->user_id,
            BattleRewardStepName::XP->value,
            'You gained 150 XP.',
        );

        $this->assertSame(
            1,
            CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)->count(),
        );
    }

    public function testEmitUnemittedMessagesEmitsAndMarksEmitted(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);
        $message = $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => null,
        ]);

        $count = resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);

        $this->assertSame(1, $count);
        $this->assertNotNull($message->refresh()->emitted_at);
    }

    public function testEmitUnemittedMessagesSkipsAlreadyEmitted(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);
        $message = $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => now()->subSeconds(5),
        ]);

        $count = resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);

        $this->assertSame(0, $count);
        $this->assertNotNull($message->refresh()->emitted_at);
    }

    public function testBroadcastExceptionIsRaisedSoCallerCanScheduleRetry(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);
        $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => null,
        ]);
        Event::listen(ServerMessageEvent::class, function (): void {
            throw new \RuntimeException('broadcast failed');
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('broadcast failed');

        resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);
    }

    public function testBroadcastExceptionDoesNotMarkEmitted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);
        $message = $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => null,
        ]);
        Event::listen(ServerMessageEvent::class, function (): void {
            throw new \RuntimeException('broadcast failed');
        });

        try {
            resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);
            $this->fail('The failed notification must remain retryable.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('broadcast failed', $exception->getMessage());
        }

        $this->assertNull($message->refresh()->emitted_at);
    }

    public function testBroadcastExceptionLeavesMessageReplayable(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);
        $message = $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => null,
        ]);
        Event::listen(ServerMessageEvent::class, function (): void {
            throw new \RuntimeException('broadcast failed');
        });

        try {
            resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);
            $this->fail('The failed notification must remain replayable.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('broadcast failed', $exception->getMessage());
        }

        $this->assertNull($message->refresh()->emitted_at, 'Message must remain replayable after failed broadcast.');
    }

    public function testRetryAfterPreviousExceptionCanMarkEmitted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);
        $message = $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => null,
        ]);
        $throwCount = 0;
        Event::listen(ServerMessageEvent::class, function () use (&$throwCount): void {
            if ($throwCount === 0) {
                $throwCount++;
                throw new \RuntimeException('broadcast failed first time');
            }
        });

        try {
            resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);
            $this->fail('The first notification attempt must fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('broadcast failed first time', $exception->getMessage());
        }
        $this->assertNull($message->refresh()->emitted_at);

        Event::forget(ServerMessageEvent::class);
        Event::fake();

        resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);
        $this->assertNotNull($message->refresh()->emitted_at);
    }

    public function testEmittedAtIsNotOverwrittenForAlreadyEmittedMessages(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);
        $originalTime = now()->subSeconds(10);
        $message = $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => $originalTime,
        ]);

        resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);

        $refreshed = $message->refresh();
        $this->assertNotNull($refreshed->emitted_at);
        $this->assertSame($refreshed->emitted_at->toDateTimeString(), $originalTime->toDateTimeString());
    }

    public function testOutboxIsScopedToActiveRewardRequestOnly(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $requestA = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);
        $requestB = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);
        $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $requestB->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => null,
        ]);

        $count = resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($requestA);

        $this->assertSame(0, $count, 'Only messages for the given request must be emitted.');
    }

    public function testEmitUnemittedMessagesReturnsZeroWhenNoMessages(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);

        $count = resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);

        $this->assertSame(0, $count);
    }

    public function testMarkEmittedSetsTimestamp(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);
        $message = $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => null,
        ]);

        resolve(BattleRewardMessageOutboxService::class)->markEmitted($message);

        $this->assertNotNull($message->refresh()->emitted_at);
    }

    public function testMarkEmittedIsIdempotentWhenAlreadyEmitted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);
        $message = $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => now()->subSeconds(10),
        ]);
        $originalEmittedAt = $message->emitted_at;

        resolve(BattleRewardMessageOutboxService::class)->markEmitted($message);

        $this->assertTrue($message->refresh()->emitted_at->equalTo($originalEmittedAt));
    }

    public function testImmediateBroadcastFailureKeepsCompletedRewardMessageRetryableWithoutDuplicatingMutation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['copper_coins' => 100]);
        $request = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);
        $context = resolve(BattleRewardMessageContext::class);
        $context->start($request->id, $character->id, $character->user_id);
        $context->setStep(BattleRewardStepName::CURRENCY_REWARDS);
        $character->increment('copper_coins', 25);
        Event::listen(ServerMessageEvent::class, function (): void {
            throw new \RuntimeException('broadcast failed');
        });

        resolve(ServerMessageHandler::class)->sendBasicMessage($character->user, 'You gained 25 Copper Coins.');

        $message = CharacterBattleRewardRequestMessage::where(
            'character_battle_reward_request_id',
            $request->id,
        )->firstOrFail();
        $this->assertSame(125, $character->refresh()->copper_coins);
        $this->assertNull($message->emitted_at);

        Event::forget(ServerMessageEvent::class);
        Event::fake();
        resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);

        $this->assertNotNull($message->refresh()->emitted_at);
        $this->assertSame(125, $character->refresh()->copper_coins);
        $context->clear();
    }
}
