<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequestMessage;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageContext;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageOutboxService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBattleReward;

class BattleRewardMessageOutboxServiceTest extends TestCase
{
    use CreateCharacterBattleReward, RefreshDatabase;

    public function test_store_message_creates_record(): void
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

    public function test_emit_unemitted_messages_emits_and_marks_emitted(): void
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

    public function test_emit_unemitted_messages_skips_already_emitted(): void
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

    public function test_broadcast_exception_is_raised_so_caller_can_schedule_retry(): void
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

    public function test_emitted_at_is_not_overwritten_for_already_emitted_messages(): void
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

    public function test_outbox_is_scoped_to_active_reward_request_only(): void
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

    public function test_emit_unemitted_messages_returns_zero_when_no_messages(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest(['character_id' => $character->id]);

        $count = resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);

        $this->assertSame(0, $count);
    }

    public function test_mark_emitted_sets_timestamp(): void
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

    public function test_mark_emitted_is_idempotent_when_already_emitted(): void
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

    public function test_immediate_broadcast_failure_keeps_completed_reward_message_retryable_without_duplicating_mutation(): void
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
