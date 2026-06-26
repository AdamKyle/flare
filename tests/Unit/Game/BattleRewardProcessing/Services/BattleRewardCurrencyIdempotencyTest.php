<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Services\CharacterRewardService;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class BattleRewardCurrencyIdempotencyTest extends TestCase
{
    use CreateMonster, MockeryPHPUnitIntegration, RefreshDatabase;

    public function testSavedCurrencyPayloadIsReusedOnResume(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::CURRENCY_REWARDS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::CURRENCY_REWARDS)->update([
            'payload_json' => ['plan' => ['gold' => 25, 'copper_coins' => 0, 'event' => ['active' => false]]],
        ]);
        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->once()->andReturnSelf();
        $characterRewardService->shouldReceive('planCurrencies')->never();
        $characterRewardService->shouldReceive('applyPlannedCurrencies')->once()->with(['gold' => 25, 'copper_coins' => 0, 'event' => ['active' => false]])->andReturn(['gold' => 25]);
        $this->instance(CharacterRewardService::class, $characterRewardService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::CURRENCY_REWARDS)->firstOrFail()->status);
    }

    public function testCompletedCurrencyStepCannotApplyTwice(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('planCurrencies')->never();
        $characterRewardService->shouldReceive('applyPlannedCurrencies')->never();
        $this->instance(CharacterRewardService::class, $characterRewardService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::CURRENCY_REWARDS)->firstOrFail()->status);
    }

    public function testCurrencyPayloadSavedBeforeFailedApplyIsReused(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::CURRENCY_REWARDS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $firstCharacterRewardService = Mockery::mock(CharacterRewardService::class);
        $firstCharacterRewardService->shouldReceive('setCharacter')->twice()->andReturnSelf();
        $firstCharacterRewardService->shouldReceive('planCurrencies')->once()->andReturn(['gold' => 40, 'copper_coins' => 0, 'event' => ['active' => false]]);
        $firstCharacterRewardService->shouldReceive('applyPlannedCurrencies')->once()->andThrow(new RuntimeException('after plan'));
        $this->instance(CharacterRewardService::class, $firstCharacterRewardService);

        try {
            resolve(BattleRewardService::class)->processLedgerAwareRewards($request);
        } catch (RuntimeException) {
        }

        $secondCharacterRewardService = Mockery::mock(CharacterRewardService::class);
        $secondCharacterRewardService->shouldReceive('setCharacter')->once()->andReturnSelf();
        $secondCharacterRewardService->shouldReceive('planCurrencies')->never();
        $secondCharacterRewardService->shouldReceive('applyPlannedCurrencies')->once()->with(['gold' => 40, 'copper_coins' => 0, 'event' => ['active' => false]])->andReturn(['gold' => 40]);
        $this->instance(CharacterRewardService::class, $secondCharacterRewardService);

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::CURRENCY_REWARDS)->firstOrFail()->status);
    }
}
