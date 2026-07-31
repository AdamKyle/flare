<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequestMessage;
use App\Flare\Services\CharacterRewardService;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageOutboxService;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBattleReward;
use Tests\Traits\CreateMonster;

class BattleRewardXpCheckpointResumeTest extends TestCase
{
    use CreateCharacterBattleReward, CreateMonster, MockeryPHPUnitIntegration, RefreshDatabase;

    public function test_xp_payload_is_saved_before_apply_and_checkpointed(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::XP)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->twice()->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->once()->andReturn(150);
        $characterRewardService->shouldReceive('distributeCheckpointedXp')->once()->withArgs(function (int $xp, callable $callback): bool {
            $callback($xp, 0, Character::first());

            return $xp === 150;
        })->andReturnSelf();
        $this->instance(CharacterRewardService::class, $characterRewardService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $step = $request->steps()->where('step_name', BattleRewardStepName::XP)->firstOrFail();
        $this->assertSame(150, $step->payload_json['total_xp']);
        $this->assertSame(0, $step->checkpoint_json['remaining_xp']);
    }

    public function test_xp_resume_uses_remaining_checkpoint_without_recalculating(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::XP)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::XP)->update([
            'payload_json' => ['total_xp' => 500, 'starting_level' => $character->level, 'starting_xp' => $character->xp],
            'checkpoint_json' => ['remaining_xp' => 125],
        ]);
        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->once()->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->never();
        $characterRewardService->shouldReceive('distributeCheckpointedXp')->once()->withArgs(function (int $xp, callable $callback): bool {
            $callback($xp, 0, Character::first());

            return $xp === 125;
        })->andReturnSelf();
        $this->instance(CharacterRewardService::class, $characterRewardService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::XP)->firstOrFail()->status);
    }

    public function test_xp_step_resumable_after_interrupt_preserves_checkpoint_json(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::XP)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::XP)->update([
            'status' => BattleRewardStepStatus::CHECKPOINTED,
            'payload_json' => ['total_xp' => 300, 'starting_level' => $character->level, 'starting_xp' => $character->xp],
            'checkpoint_json' => ['remaining_xp' => 200],
        ]);

        resolve(BattleRewardProcessingQueueManager::class)
            ->recoverLedgerBackedProcessingRequests($character->id);

        $step = $request->steps()->where('step_name', BattleRewardStepName::XP)->firstOrFail();
        $this->assertSame(BattleRewardStepStatus::RESUMABLE, $step->status);
        $this->assertSame(['remaining_xp' => 200], $step->checkpoint_json);
    }

    public function test_xp_resume_from_resumable_step_still_uses_checkpointed_xp(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::XP)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::XP)->update([
            'status' => BattleRewardStepStatus::RESUMABLE,
            'payload_json' => ['total_xp' => 300, 'starting_level' => $character->level, 'starting_xp' => $character->xp],
            'checkpoint_json' => ['remaining_xp' => 75],
        ]);
        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->once()->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->never();
        $characterRewardService->shouldReceive('distributeCheckpointedXp')->once()->withArgs(function (int $xp, callable $callback): bool {
            $callback($xp, 0, Character::first());

            return $xp === 75;
        })->andReturnSelf();
        $this->instance(CharacterRewardService::class, $characterRewardService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::XP)->firstOrFail()->status);
    }

    public function test_unemitted_xp_message_is_replayable_by_outbox_service(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        $message = $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => null,
        ]);

        $count = resolve(BattleRewardMessageOutboxService::class)
            ->emitUnemittedMessages($request);

        $this->assertSame(1, $count);
        $this->assertNotNull($message->refresh()->emitted_at);
    }

    public function test_already_emitted_xp_message_is_not_repeat_emitted(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        $emittedAt = now()->subSeconds(10);
        $message = $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => $emittedAt,
        ]);

        $count = resolve(BattleRewardMessageOutboxService::class)
            ->emitUnemittedMessages($request);

        $refreshed = $message->refresh();
        $this->assertSame(0, $count);
        $this->assertNotNull($refreshed->emitted_at);
        $this->assertSame($refreshed->emitted_at->toDateTimeString(), $emittedAt->toDateTimeString());
    }

    public function test_manual_battle_reward_stores_xp_message_when_user_shows_xp_per_kill(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->user->update(['show_xp_per_kill' => true]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'xp' => 150, 'max_level' => 9999]);
        DB::table('sessions')->insert([[
            'id' => 'manual-xp-message',
            'user_id' => $character->user_id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::XP)->update(['status' => BattleRewardStepStatus::COMPLETED]);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $message = CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)
            ->where('message', 'like', 'You gained:%')
            ->firstOrFail();
        $this->assertSame(BattleRewardStepName::XP, $message->step_name);
        $this->assertStringContainsString('You gained:', $message->message);
        $this->assertStringContainsString('150 XP', $message->message);
    }

    public function test_manual_battle_reward_does_not_store_xp_message_when_user_hides_xp_per_kill(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->user->update(['show_xp_per_kill' => false]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'xp' => 150, 'max_level' => 9999]);
        DB::table('sessions')->insert([[
            'id' => 'manual-xp-message-hidden',
            'user_id' => $character->user_id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::XP)->update(['status' => BattleRewardStepStatus::COMPLETED]);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(0, CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)->where('message', 'like', 'You gained:%')->count());
    }

    public function test_resumed_manual_battle_reward_does_not_duplicate_xp_message(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->user->update(['show_xp_per_kill' => true]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'xp' => 150, 'max_level' => 9999]);
        DB::table('sessions')->insert([[
            'id' => 'manual-xp-message-resume',
            'user_id' => $character->user_id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::XP)->update(['status' => BattleRewardStepStatus::COMPLETED]);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $request->steps()->where('step_name', BattleRewardStepName::XP)->update([
            'status' => BattleRewardStepStatus::RESUMABLE,
            'checkpoint_json' => ['remaining_xp' => 0],
            'completed_at' => null,
        ]);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request->refresh());

        $this->assertSame(1, CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)->where('step_name', BattleRewardStepName::XP)->where('message', 'like', 'You gained:%')->count());
    }

    public function test_exploration_reward_stores_exploration_xp_message_without_manual_xp_message(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->user->update([
            'show_xp_per_kill' => true,
            'show_xp_for_exploration' => true,
        ]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'xp' => 150, 'max_level' => 9999]);
        DB::table('sessions')->insert([[
            'id' => 'exploration-xp-message',
            'user_id' => $character->user_id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::EXPLORATION,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => [
                'total_creatures' => 3,
                'total_xp' => 450,
            ]],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::XP)->update(['status' => BattleRewardStepStatus::COMPLETED]);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $message = CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)
            ->where('message', 'like', 'You slaughtered:%')
            ->firstOrFail();
        $this->assertStringContainsString('You slaughtered:', $message->message);
        $this->assertStringNotContainsString('You gained:', $message->message);
        $this->assertSame(0, CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)->where('message', 'like', 'You gained:%')->count());
    }

    public function test_resumed_xp_step_does_not_duplicate_level_up_effects(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->user->update(['show_xp_per_kill' => false]);
        $character->update([
            'level' => 1,
            'xp' => 90,
            'xp_next' => 100,
        ]);
        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'xp' => 10,
            'max_level' => 9999,
        ]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::XP)->update(['status' => BattleRewardStepStatus::COMPLETED]);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $characterAfterFirstPass = $character->refresh();
        $levelAfterFirstPass = $characterAfterFirstPass->level;
        $xpAfterFirstPass = $characterAfterFirstPass->xp;
        $xpNextAfterFirstPass = $characterAfterFirstPass->xp_next;
        $levelUpMessageCountAfterFirstPass = CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)
            ->where('step_name', BattleRewardStepName::XP)
            ->where('message', 'like', '%level%')
            ->count();
        $xpStep = $request->steps()->where('step_name', BattleRewardStepName::XP)->firstOrFail();
        $checkpointAfterFirstPass = $xpStep->checkpoint_json;

        $xpStep->update([
            'status' => BattleRewardStepStatus::RESUMABLE,
            'checkpoint_json' => ['remaining_xp' => 0],
            'completed_at' => null,
        ]);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request->refresh());

        $characterAfterResume = $character->refresh();
        $this->assertSame($levelAfterFirstPass, $characterAfterResume->level);
        $this->assertSame($xpAfterFirstPass, $characterAfterResume->xp);
        $this->assertSame($xpNextAfterFirstPass, $characterAfterResume->xp_next);
        $this->assertSame($levelUpMessageCountAfterFirstPass, CharacterBattleRewardRequestMessage::where('character_battle_reward_request_id', $request->id)->where('step_name', BattleRewardStepName::XP)->where('message', 'like', '%level%')->count());
        $this->assertSame($checkpointAfterFirstPass['current_level'], $request->steps()->where('step_name', BattleRewardStepName::XP)->firstOrFail()->checkpoint_json['current_level']);
    }
}
