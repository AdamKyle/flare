<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestMessage;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageOutboxService;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class BattleRewardMessageOutboxServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_message_creates_record(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);

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
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        $message = CharacterBattleRewardRequestMessage::factory()->create([
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
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        $message = CharacterBattleRewardRequestMessage::factory()->create([
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

    public function test_broadcast_exception_does_not_throw_out_of_emit_unemitted_messages(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        CharacterBattleRewardRequestMessage::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => null,
        ]);
        Event::listen(ServerMessageEvent::class, function (): void {
            throw new \RuntimeException('broadcast failed');
        });

        $count = resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);

        $this->assertSame(0, $count);
    }

    public function test_broadcast_exception_does_not_mark_emitted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        $message = CharacterBattleRewardRequestMessage::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => null,
        ]);
        Event::listen(ServerMessageEvent::class, function (): void {
            throw new \RuntimeException('broadcast failed');
        });

        resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);

        $this->assertNull($message->refresh()->emitted_at);
    }

    public function test_broadcast_exception_leaves_message_replayable(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        $message = CharacterBattleRewardRequestMessage::factory()->create([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => null,
        ]);
        Event::listen(ServerMessageEvent::class, function (): void {
            throw new \RuntimeException('broadcast failed');
        });

        resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);

        $this->assertNull($message->refresh()->emitted_at, 'Message must remain replayable after failed broadcast.');
    }

    public function test_retry_after_previous_exception_can_mark_emitted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        $message = CharacterBattleRewardRequestMessage::factory()->create([
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

        resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);
        $this->assertNull($message->refresh()->emitted_at);

        Event::forget(ServerMessageEvent::class);
        Event::fake();

        resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);
        $this->assertNotNull($message->refresh()->emitted_at);
    }

    public function test_emitted_at_is_not_overwritten_for_already_emitted_messages(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        $originalTime = now()->subSeconds(10);
        $message = CharacterBattleRewardRequestMessage::factory()->create([
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
        $requestA = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        $requestB = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        CharacterBattleRewardRequestMessage::factory()->create([
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
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);

        $count = resolve(BattleRewardMessageOutboxService::class)->emitUnemittedMessages($request);

        $this->assertSame(0, $count);
    }

    public function test_mark_emitted_sets_timestamp(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        $message = CharacterBattleRewardRequestMessage::factory()->create([
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
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        $message = CharacterBattleRewardRequestMessage::factory()->create([
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
}
