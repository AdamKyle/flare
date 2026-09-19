<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\BattleRewardProcessing\Services\BattleRewardSharedContextService;
use App\Game\BattleRewardProcessing\Services\BattleRewardStepPlanService;
use App\Game\BattleRewardProcessing\Services\CharacterRewardService;
use App\Game\Gems\Values\ResolvedAreaGemEffects;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBattleReward;
use Tests\Traits\CreateMonster;

class BattleRewardCurrencyIdempotencyTest extends TestCase
{
    use CreateCharacterBattleReward, CreateMonster, MockeryPHPUnitIntegration, RefreshDatabase;

    public function test_saved_currency_payload_is_reused_on_resume(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request, resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster)));
        $request->steps()->where('step_name', '!=', BattleRewardStepName::CURRENCY_REWARDS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::CURRENCY_REWARDS)->update([
            'payload_json' => ['plan' => ['gold' => 25, 'copper_coins' => 0, 'event' => ['active' => false]]],
        ]);
        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->once()->andReturnSelf();
        $characterRewardService->shouldReceive('planCurrencies')->never();
        $characterRewardService->shouldReceive('applyPlannedCurrencies')->once()->with(['gold' => 25, 'copper_coins' => 0, 'event' => ['active' => false]], Mockery::type(ResolvedAreaGemEffects::class))->andReturn(['gold' => 25]);
        $characterRewardService->shouldReceive('currencyCalculationFailure')->andReturn(null);
        $this->instance(CharacterRewardService::class, $characterRewardService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster));

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::CURRENCY_REWARDS)->firstOrFail()->status);
    }

    public function test_completed_currency_step_cannot_apply_twice(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request, resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster)));
        $request->steps()->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('planCurrencies')->never();
        $characterRewardService->shouldReceive('applyPlannedCurrencies')->never();
        $this->instance(CharacterRewardService::class, $characterRewardService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster));

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::CURRENCY_REWARDS)->firstOrFail()->status);
    }

    public function test_failed_apply_after_planning_returns_a_failed_result_preserving_the_original_exception(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request, resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster)));
        $request->steps()->where('step_name', '!=', BattleRewardStepName::CURRENCY_REWARDS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->once()->andReturnSelf();
        $characterRewardService->shouldReceive('planCurrencies')->once()->andReturn(['gold' => 40, 'copper_coins' => 0, 'event' => ['active' => false]]);
        $characterRewardService->shouldReceive('applyPlannedCurrencies')->once()->with(['gold' => 40, 'copper_coins' => 0, 'event' => ['active' => false]], Mockery::type(ResolvedAreaGemEffects::class))->andThrow(new RuntimeException('after plan'));
        $this->instance(CharacterRewardService::class, $characterRewardService);

        $result = resolve(BattleRewardService::class)->processLedgerAwareRewards($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster));

        $this->assertFalse($result->successful());
        $this->assertInstanceOf(RuntimeException::class, $result->failure());
        $this->assertSame('after plan', $result->failure()->getMessage());
        $this->assertSame(BattleRewardStepStatus::FAILED, $request->steps()->where('step_name', BattleRewardStepName::CURRENCY_REWARDS)->firstOrFail()->status);
    }

    public function test_second_attempt_reuses_previously_saved_plan_without_replanning(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request, resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster)));
        $request->steps()->where('step_name', '!=', BattleRewardStepName::CURRENCY_REWARDS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::CURRENCY_REWARDS)->update([
            'status' => BattleRewardStepStatus::FAILED,
            'payload_json' => ['plan' => ['gold' => 40, 'copper_coins' => 0, 'event' => ['active' => false]]],
        ]);
        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->once()->andReturnSelf();
        $characterRewardService->shouldReceive('planCurrencies')->never();
        $characterRewardService->shouldReceive('applyPlannedCurrencies')->once()->with(['gold' => 40, 'copper_coins' => 0, 'event' => ['active' => false]], Mockery::type(ResolvedAreaGemEffects::class))->andReturn(['gold' => 40]);
        $characterRewardService->shouldReceive('currencyCalculationFailure')->andReturn(null);
        $this->instance(CharacterRewardService::class, $characterRewardService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster));

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::CURRENCY_REWARDS)->firstOrFail()->status);
    }
}
