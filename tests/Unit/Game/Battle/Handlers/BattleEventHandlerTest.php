<?php

namespace Tests\Unit\Game\Battle\Handlers;

use App\Flare\Models\BatchCrafting;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;

class BattleEventHandlerTest extends TestCase
{
    use CreateBatchCrafting, MockeryPHPUnitIntegration, RefreshDatabase;

    public function testBattleRewardUsesSecondPriorityAndPreservesPayload(): void
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

        (new BattleEventHandler($queueManager, Mockery::mock(WeeklyBattleService::class), Mockery::mock(BatchCraftingService::class)))
            ->processMonsterDeath(10, 20, ['attack_type' => 'attack']);
    }

    public function testExplorationRewardUsesSecondPriority(): void
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

        (new BattleEventHandler($queueManager, Mockery::mock(WeeklyBattleService::class), Mockery::mock(BatchCraftingService::class)))
            ->processMonsterDeath(10, 20, ['exploration_log_id' => 30]);
    }

    public function testProcessDeadCharacterEndsBatchCrafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);

        resolve(BattleEventHandler::class)->processDeadCharacter($character);

        $this->assertSame(BatchCraftingEndReason::DIED->value, BatchCrafting::where('character_id', $character->id)->first()->ended_reason);
    }

    public function testProcessDeadCharacterSendsReviveMessageOnceAcrossRepeatedCalls(): void
    {
        Event::fake([ServerMessageEvent::class]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $battleEventHandler = resolve(BattleEventHandler::class);

        $battleEventHandler->processDeadCharacter($character);
        $battleEventHandler->processDeadCharacter($character->refresh());

        Event::assertDispatchedTimes(ServerMessageEvent::class, 1);
    }

    public function testProcessDeadCharacterRunsDeathOnlySideEffectsOnlyOnceAcrossRepeatedCalls(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $batchCraftingService = Mockery::mock(BatchCraftingService::class);
        $batchCraftingService->shouldReceive('completeForDeath')->once();

        $battleEventHandler = new BattleEventHandler(
            Mockery::mock(BattleRewardProcessingQueueManager::class),
            Mockery::mock(WeeklyBattleService::class),
            $batchCraftingService,
        );

        $battleEventHandler->processDeadCharacter($character);
        $battleEventHandler->processDeadCharacter($character->refresh());

        $this->assertTrue((bool) $character->refresh()->is_dead);
    }

}
