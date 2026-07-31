<?php

namespace Tests\Unit\Game\Battle\Handlers;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\Battle\Handlers\BattleEventHandler;
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
use Tests\Traits\CreateMonster;

class BattleEventHandlerTest extends TestCase
{
    use CreateBatchCrafting, CreateMonster, MockeryPHPUnitIntegration, RefreshDatabase;

    public function test_normal_monster_death_claims_weekly_fight_before_enqueue(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster();
        $weeklyBattleService = Mockery::mock(WeeklyBattleService::class);
        $weeklyBattleService->shouldReceive('claimMonsterDeath')->once()->ordered()->with(
            Mockery::on(fn ($value): bool => $value->id === $character->id),
            Mockery::on(fn ($value): bool => $value->id === $monster->id),
        );
        $queueManager = Mockery::mock(BattleRewardProcessingQueueManager::class);
        $queueManager->shouldReceive('enqueue')->once()->ordered()->with(
            $character->id,
            BattleRewardRequestPriority::SECOND,
            BattleRewardRequestSourceType::BATTLE,
            Mockery::on(fn (string $sourceId): bool => str_starts_with($sourceId, 'battle:'.$character->id.':'.$monster->id.':')),
            ['character_id' => $character->id, 'monster_id' => $monster->id, 'context' => []],
        )->andReturn(Mockery::mock(CharacterBattleRewardRequest::class));

        (new BattleEventHandler($queueManager, $weeklyBattleService, Mockery::mock(BatchCraftingService::class)))->processMonsterDeath($character->id, $monster->id);
    }

    public function test_exploration_monster_death_claims_weekly_fight_before_enqueue(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $monster = $this->createMonster();
        $weeklyBattleService = Mockery::mock(WeeklyBattleService::class);
        $weeklyBattleService->shouldReceive('claimMonsterDeath')->once()->ordered();
        $queueManager = Mockery::mock(BattleRewardProcessingQueueManager::class);
        $queueManager->shouldReceive('enqueue')->once()->ordered()->with(
            $character->id,
            BattleRewardRequestPriority::SECOND,
            BattleRewardRequestSourceType::EXPLORATION,
            Mockery::on(fn (string $sourceId): bool => str_starts_with($sourceId, 'exploration:'.$character->id.':30:'.$monster->id.':')),
            ['character_id' => $character->id, 'monster_id' => $monster->id, 'context' => ['exploration_log_id' => 30]],
        )->andReturn(Mockery::mock(CharacterBattleRewardRequest::class));

        (new BattleEventHandler($queueManager, $weeklyBattleService, Mockery::mock(BatchCraftingService::class)))->processMonsterDeath($character->id, $monster->id, ['exploration_log_id' => 30]);
    }

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

        $weeklyBattleService = Mockery::mock(WeeklyBattleService::class);
        $weeklyBattleService->shouldNotReceive('claimMonsterDeath');

        (new BattleEventHandler($queueManager, $weeklyBattleService, Mockery::mock(BatchCraftingService::class)))
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

        $weeklyBattleService = Mockery::mock(WeeklyBattleService::class);
        $weeklyBattleService->shouldNotReceive('claimMonsterDeath');

        (new BattleEventHandler($queueManager, $weeklyBattleService, Mockery::mock(BatchCraftingService::class)))
            ->processMonsterDeath(10, 20, ['exploration_log_id' => 30]);
    }

    public function test_process_dead_character_ends_batch_crafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);

        resolve(BattleEventHandler::class)->processDeadCharacter($character);

        $this->assertSame(BatchCraftingEndReason::DIED->value, BatchCrafting::where('character_id', $character->id)->first()->ended_reason);
    }

    public function test_process_dead_character_sends_revive_message_once_across_repeated_calls(): void
    {
        Event::fake([ServerMessageEvent::class]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $battleEventHandler = resolve(BattleEventHandler::class);

        $battleEventHandler->processDeadCharacter($character);
        $battleEventHandler->processDeadCharacter($character->refresh());

        Event::assertDispatchedTimes(ServerMessageEvent::class, 1);
    }

    public function test_process_dead_character_runs_death_only_side_effects_only_once_across_repeated_calls(): void
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
