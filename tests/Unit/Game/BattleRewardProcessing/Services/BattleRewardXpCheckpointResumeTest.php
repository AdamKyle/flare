<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestMessage;
use App\Flare\Services\CharacterRewardService;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageOutboxService;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class BattleRewardXpCheckpointResumeTest extends TestCase
{
    use CreateMonster, MockeryPHPUnitIntegration, RefreshDatabase;

    public function test_xp_payload_is_saved_before_apply_and_checkpointed(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = CharacterBattleRewardRequest::factory()->create([
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
        $request = CharacterBattleRewardRequest::factory()->create([
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
        $request = CharacterBattleRewardRequest::factory()->create([
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
        $request = CharacterBattleRewardRequest::factory()->create([
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
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        $message = CharacterBattleRewardRequestMessage::factory()->create([
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
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        $emittedAt = now()->subSeconds(10);
        $message = CharacterBattleRewardRequestMessage::factory()->create([
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
}
