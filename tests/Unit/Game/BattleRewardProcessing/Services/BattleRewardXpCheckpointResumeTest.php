<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Services\CharacterRewardService;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class BattleRewardXpCheckpointResumeTest extends TestCase
{
    use CreateMonster, MockeryPHPUnitIntegration, RefreshDatabase;

    public function testXpPayloadIsSavedBeforeApplyAndCheckpointed(): void
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

    public function testXpResumeUsesRemainingCheckpointWithoutRecalculating(): void
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
}
