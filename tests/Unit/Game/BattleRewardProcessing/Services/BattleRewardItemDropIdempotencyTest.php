<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\Core\Services\DropCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class BattleRewardItemDropIdempotencyTest extends TestCase
{
    use CreateMonster, MockeryPHPUnitIntegration, RefreshDatabase;

    public function test_saved_item_drop_payload_is_reused_on_resume(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'payload_json' => ['plan' => ['drops' => [['item_id' => 1, 'is_mythic' => false, 'source' => 'monster_drop']]]],
        ]);
        $dropCheckService = Mockery::mock(DropCheckService::class);
        $dropCheckService->shouldReceive('planDrops')->never();
        $dropCheckService->shouldReceive('applyPlannedDrops')->once()->andReturn([]);
        $this->instance(DropCheckService::class, $dropCheckService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->firstOrFail()->status);
    }

    public function test_completed_item_drop_step_cannot_apply_twice(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $dropCheckService = Mockery::mock(DropCheckService::class);
        $dropCheckService->shouldReceive('planDrops')->never();
        $dropCheckService->shouldReceive('applyPlannedDrops')->never();
        $this->instance(DropCheckService::class, $dropCheckService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->firstOrFail()->status);
    }

    public function test_running_item_drop_step_with_saved_plan_reuses_existing_plan_on_resume(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'status' => BattleRewardStepStatus::RUNNING,
            'payload_json' => ['plan' => ['drops' => [['item_id' => 1, 'is_mythic' => false, 'source' => 'monster_drop']]]],
        ]);
        $dropCheckService = Mockery::mock(DropCheckService::class);
        $dropCheckService->shouldReceive('planDrops')->never();
        $dropCheckService->shouldReceive('applyPlannedDrops')->once()->andReturn([]);
        $this->instance(DropCheckService::class, $dropCheckService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->firstOrFail()->status);
    }

    public function test_resumable_item_drop_step_with_saved_plan_reuses_existing_plan_on_resume(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'status' => BattleRewardStepStatus::RESUMABLE,
            'payload_json' => ['plan' => ['drops' => [['item_id' => 1, 'is_mythic' => false, 'source' => 'monster_drop']]]],
        ]);
        $dropCheckService = Mockery::mock(DropCheckService::class);
        $dropCheckService->shouldReceive('planDrops')->never();
        $dropCheckService->shouldReceive('applyPlannedDrops')->once()->andReturn([]);
        $this->instance(DropCheckService::class, $dropCheckService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->firstOrFail()->status);
    }

    public function test_item_drop_plan_item_id_is_preserved_on_resume(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $originalPlan = ['drops' => [['item_id' => 42, 'is_mythic' => true, 'source' => 'monster_drop']]];
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'payload_json' => ['plan' => $originalPlan],
        ]);
        $capturedPlan = null;
        $dropCheckService = Mockery::mock(DropCheckService::class);
        $dropCheckService->shouldReceive('planDrops')->never();
        $dropCheckService->shouldReceive('applyPlannedDrops')
            ->once()
            ->withArgs(function ($char, $mon, array $plan) use (&$capturedPlan): bool {
                $capturedPlan = $plan;

                return true;
            })
            ->andReturn([]);
        $this->instance(DropCheckService::class, $dropCheckService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(42, $capturedPlan['drops'][0]['item_id']);
        $this->assertTrue($capturedPlan['drops'][0]['is_mythic']);
    }
}
