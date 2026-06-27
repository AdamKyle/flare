<?php

namespace Tests\Unit\Game\Battle\Handlers;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class BattleEventHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_battle_reward_uses_second_priority_and_preserves_payload(): void
    {
        $queueManager = Mockery::mock(BattleRewardProcessingQueueManager::class);
        $queueManager->shouldReceive('enqueue')
            ->once()
            ->with(
                10,
                BattleRewardRequestPriority::SECOND,
                BattleRewardRequestSourceType::BATTLE,
                Mockery::on(fn (string $sourceId): bool => str_starts_with(
                    $sourceId,
                    'battle:10:20:',
                )),
                [
                    'character_id' => 10,
                    'monster_id' => 20,
                    'context' => ['attack_type' => 'attack'],
                ],
            )
            ->andReturn(Mockery::mock(CharacterBattleRewardRequest::class));

        (new BattleEventHandler($queueManager, Mockery::mock(WeeklyBattleService::class)))
            ->processMonsterDeath(10, 20, ['attack_type' => 'attack']);
    }

    public function test_exploration_reward_uses_second_priority(): void
    {
        $queueManager = Mockery::mock(BattleRewardProcessingQueueManager::class);
        $queueManager->shouldReceive('enqueue')
            ->once()
            ->with(
                10,
                BattleRewardRequestPriority::SECOND,
                BattleRewardRequestSourceType::EXPLORATION,
                Mockery::on(fn (string $sourceId): bool => str_starts_with(
                    $sourceId,
                    'exploration:10:30:20:',
                )),
                [
                    'character_id' => 10,
                    'monster_id' => 20,
                    'context' => ['exploration_log_id' => 30],
                ],
            )
            ->andReturn(Mockery::mock(CharacterBattleRewardRequest::class));

        (new BattleEventHandler($queueManager, Mockery::mock(WeeklyBattleService::class)))
            ->processMonsterDeath(10, 20, ['exploration_log_id' => 30]);
    }
}
