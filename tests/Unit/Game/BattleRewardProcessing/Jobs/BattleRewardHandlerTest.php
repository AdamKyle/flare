<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Jobs;

use App\Game\BattleRewardProcessing\Jobs\BattleRewardHandler;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\Core\Services\CharacterRewardLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class BattleRewardHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    public function testHandleCallsBattleRewardServiceWithIdsContextAndIncludesWinterEvent(): void
    {
        $characterId = 123;
        $monsterId = 456;
        $context = [
            'total_xp' => 100,
            'total_creatures' => 5,
            'total_faction_points' => 25,
        ];

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('setUp')->once()->with($characterId, $monsterId)->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->once()->with($context)->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->once()->with(true);
        $characterRewardLockService = Mockery::mock(CharacterRewardLockService::class);
        $characterRewardLockService->shouldReceive('run')
            ->once()
            ->with($characterId, Mockery::type('callable'))
            ->andReturnUsing(function (int $lockedCharacterId, callable $callback): void {
                $callback();
            });

        $job = new BattleRewardHandler($characterId, $monsterId, $context);

        $job->handle($battleRewardService, $characterRewardLockService);
    }

    public function testHandleUsesEmptyContextWhenNotProvided(): void
    {
        $characterId = 123;
        $monsterId = 456;

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('setUp')->once()->with($characterId, $monsterId)->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->once()->with([])->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->once()->with(true);
        $characterRewardLockService = Mockery::mock(CharacterRewardLockService::class);
        $characterRewardLockService->shouldReceive('run')
            ->once()
            ->andReturnUsing(function (int $lockedCharacterId, callable $callback): void {
                $callback();
            });

        $job = new BattleRewardHandler($characterId, $monsterId);

        $job->handle($battleRewardService, $characterRewardLockService);
    }
}
