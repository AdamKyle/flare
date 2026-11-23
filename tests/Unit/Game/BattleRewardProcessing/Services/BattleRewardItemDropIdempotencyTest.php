<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use Tests\Traits\CreateCharacterBattleReward;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\MonitoredSystemErrorOccurrence;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Services\DropCheckService;
use App\Game\Skills\Services\SkillCheckService;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;

class BattleRewardItemDropIdempotencyTest extends TestCase
{
    use CreateCharacterBattleReward, CreateItem, CreateMonster, MockeryPHPUnitIntegration, RefreshDatabase;

    public function testSavedItemDropPayloadIsReusedOnResume(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
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

    public function testCompletedItemDropStepCannotApplyTwice(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
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

    public function testRunningItemDropStepWithSavedPlanReusesExistingPlanOnResume(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
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

    public function testResumableItemDropStepWithSavedPlanReusesExistingPlanOnResume(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
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

    public function testItemDropPlanItemIdIsPreservedOnResume(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $request = $this->createCharacterBattleRewardRequest([
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

    public function testDisenchantTopBarUpdateRunsAfterItemDropStepCommits(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => 0]);
        $character->user->update(['auto_disenchant' => true, 'auto_disenchant_amount' => 'all', 'auto_sell_item' => false]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'payload_json' => ['plan' => ['drops' => [['item_id' => $item->id, 'is_mythic' => false]], 'game_map_bonus' => 0, 'looting_chance' => 0]],
        ]);
        $this->instance(SkillCheckService::class, Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getDCCheck')->andReturn(1);
            $mock->shouldReceive('characterRoll')->andReturn(100);
        }));
        $recordedTransactionLevels = [];
        Event::listen(UpdateTopBarEvent::class, function () use (&$recordedTransactionLevels): void {
            $recordedTransactionLevels[] = DB::transactionLevel();
        });

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame([1], $recordedTransactionLevels);
    }

    public function testFailedDisenchantTopBarUpdateKeepsItemDropStepCompleted(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => 0]);
        $character->user->update(['auto_disenchant' => true, 'auto_disenchant_amount' => 'all', 'auto_sell_item' => false]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'payload_json' => ['plan' => ['drops' => [['item_id' => $item->id, 'is_mythic' => false]], 'game_map_bonus' => 0, 'looting_chance' => 0]],
        ]);
        $this->instance(SkillCheckService::class, Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getDCCheck')->andReturn(1);
            $mock->shouldReceive('characterRoll')->andReturn(100);
        }));
        Event::listen(UpdateTopBarEvent::class, function (): void {
            throw new BroadcastException('The broadcast server could not be reached.');
        });

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->firstOrFail()->status);
    }

    public function testFailedDisenchantTopBarUpdateKeepsAwardedGoldDust(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => 0]);
        $character->user->update(['auto_disenchant' => true, 'auto_disenchant_amount' => 'all', 'auto_sell_item' => false]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'payload_json' => ['plan' => ['drops' => [['item_id' => $item->id, 'is_mythic' => false]], 'game_map_bonus' => 0, 'looting_chance' => 0]],
        ]);
        $this->instance(SkillCheckService::class, Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getDCCheck')->andReturn(1);
            $mock->shouldReceive('characterRoll')->andReturn(100);
        }));
        Event::listen(UpdateTopBarEvent::class, function (): void {
            throw new BroadcastException('The broadcast server could not be reached.');
        });

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertGreaterThan(0, $character->refresh()->gold_dust);
    }

    public function testFailedDisenchantTopBarUpdateIsReported(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => 0]);
        $character->user->update(['auto_disenchant' => true, 'auto_disenchant_amount' => 'all', 'auto_sell_item' => false]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'payload_json' => ['plan' => ['drops' => [['item_id' => $item->id, 'is_mythic' => false]], 'game_map_bonus' => 0, 'looting_chance' => 0]],
        ]);
        $this->instance(SkillCheckService::class, Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getDCCheck')->andReturn(1);
            $mock->shouldReceive('characterRoll')->andReturn(100);
        }));
        Event::listen(UpdateTopBarEvent::class, function (): void {
            throw new BroadcastException('The broadcast server could not be reached.');
        });

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertCount(1, MonitoredSystemErrorOccurrence::where('message', 'Top bar update after disenchant failed.')->get());
    }

    public function testDisenchantInventoryCountUpdateRunsAfterItemDropStepCommits(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => 0]);
        $character->user->update(['auto_disenchant' => true, 'auto_disenchant_amount' => 'all', 'auto_sell_item' => false]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'payload_json' => ['plan' => ['drops' => [['item_id' => $item->id, 'is_mythic' => false]], 'game_map_bonus' => 0, 'looting_chance' => 0]],
        ]);
        $this->instance(SkillCheckService::class, Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getDCCheck')->andReturn(1);
            $mock->shouldReceive('characterRoll')->andReturn(100);
        }));
        $recordedTransactionLevels = [];
        Event::listen(UpdateCharacterInventoryCountEvent::class, function () use (&$recordedTransactionLevels): void {
            $recordedTransactionLevels[] = DB::transactionLevel();
        });

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame([1], $recordedTransactionLevels);
    }

    public function testFailedDisenchantInventoryCountUpdateKeepsItemDropStepCompleted(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => 0]);
        $character->user->update(['auto_disenchant' => true, 'auto_disenchant_amount' => 'all', 'auto_sell_item' => false]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'payload_json' => ['plan' => ['drops' => [['item_id' => $item->id, 'is_mythic' => false]], 'game_map_bonus' => 0, 'looting_chance' => 0]],
        ]);
        $this->instance(SkillCheckService::class, Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getDCCheck')->andReturn(1);
            $mock->shouldReceive('characterRoll')->andReturn(100);
        }));
        Event::listen(UpdateCharacterInventoryCountEvent::class, function (): void {
            throw new BroadcastException('The broadcast server could not be reached.');
        });

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->firstOrFail()->status);
    }

    public function testCappedDisenchantInventoryCountUpdateRunsAfterItemDropStepCommits(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST]);
        $character->user->update(['auto_disenchant' => true, 'auto_disenchant_amount' => 'all', 'auto_sell_item' => false]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'payload_json' => ['plan' => ['drops' => [['item_id' => $item->id, 'is_mythic' => false]], 'game_map_bonus' => 0, 'looting_chance' => 0]],
        ]);
        $this->instance(SkillCheckService::class, Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getDCCheck')->andReturn(1);
            $mock->shouldReceive('characterRoll')->andReturn(100);
        }));
        $recordedTransactionLevels = [];
        Event::listen(UpdateCharacterInventoryCountEvent::class, function () use (&$recordedTransactionLevels): void {
            $recordedTransactionLevels[] = DB::transactionLevel();
        });

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame([1], $recordedTransactionLevels);
    }

    public function testFailedDisenchantRollTopBarUpdateRunsAfterItemDropStepCommits(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => 0]);
        $character->user->update(['auto_disenchant' => true, 'auto_disenchant_amount' => 'all', 'auto_sell_item' => false]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'payload_json' => ['plan' => ['drops' => [['item_id' => $item->id, 'is_mythic' => false]], 'game_map_bonus' => 0, 'looting_chance' => 0]],
        ]);
        $this->instance(SkillCheckService::class, Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getDCCheck')->andReturn(100);
            $mock->shouldReceive('characterRoll')->andReturn(1);
        }));
        $recordedTransactionLevels = [];
        Event::listen(UpdateTopBarEvent::class, function () use (&$recordedTransactionLevels): void {
            $recordedTransactionLevels[] = DB::transactionLevel();
        });

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame([1], $recordedTransactionLevels);
    }

    public function testFailedCappedDisenchantInventoryCountUpdateKeepsItemDropStepCompleted(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST]);
        $character->user->update(['auto_disenchant' => true, 'auto_disenchant_amount' => 'all', 'auto_sell_item' => false]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'payload_json' => ['plan' => ['drops' => [['item_id' => $item->id, 'is_mythic' => false]], 'game_map_bonus' => 0, 'looting_chance' => 0]],
        ]);
        $this->instance(SkillCheckService::class, Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getDCCheck')->andReturn(1);
            $mock->shouldReceive('characterRoll')->andReturn(100);
        }));
        Event::listen(UpdateCharacterInventoryCountEvent::class, function (): void {
            throw new BroadcastException('The broadcast server could not be reached.');
        });

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertSame(BattleRewardStepStatus::COMPLETED, $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->firstOrFail()->status);
    }

    public function testFailedDisenchantInventoryCountUpdateIsReported(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => 0]);
        $character->user->update(['auto_disenchant' => true, 'auto_disenchant_amount' => 'all', 'auto_sell_item' => false]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'payload_json' => ['plan' => ['drops' => [['item_id' => $item->id, 'is_mythic' => false]], 'game_map_bonus' => 0, 'looting_chance' => 0]],
        ]);
        $this->instance(SkillCheckService::class, Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getDCCheck')->andReturn(1);
            $mock->shouldReceive('characterRoll')->andReturn(100);
        }));
        Event::listen(UpdateCharacterInventoryCountEvent::class, function (): void {
            throw new BroadcastException('The broadcast server could not be reached.');
        });

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        $this->assertCount(1, MonitoredSystemErrorOccurrence::where('message', 'Inventory count update after disenchant failed.')->get());
    }

    public function testFailedDisenchantTopBarUpdateIsLogged(): void
    {
        Queue::fake();
        Log::spy();
        Log::shouldReceive('channel')->andReturnSelf();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => 0]);
        $character->user->update(['auto_disenchant' => true, 'auto_disenchant_amount' => 'all', 'auto_sell_item' => false]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'payload_json' => ['plan' => ['drops' => [['item_id' => $item->id, 'is_mythic' => false]], 'game_map_bonus' => 0, 'looting_chance' => 0]],
        ]);
        $this->instance(SkillCheckService::class, Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getDCCheck')->andReturn(1);
            $mock->shouldReceive('characterRoll')->andReturn(100);
        }));
        Event::listen(UpdateTopBarEvent::class, function (): void {
            throw new BroadcastException('The broadcast server could not be reached.');
        });

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Top bar update after disenchant failed.', Mockery::on(
                fn (array $context): bool => $context['character_id'] === $character->id,
            ));
    }

    public function testFailedDisenchantInventoryCountUpdateIsLogged(): void
    {
        Queue::fake();
        Log::spy();
        Log::shouldReceive('channel')->andReturnSelf();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => 0]);
        $character->user->update(['auto_disenchant' => true, 'auto_disenchant_amount' => 'all', 'auto_sell_item' => false]);
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request);
        $request->steps()->where('step_name', '!=', BattleRewardStepName::ITEM_DROPS)->update(['status' => BattleRewardStepStatus::COMPLETED]);
        $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->update([
            'payload_json' => ['plan' => ['drops' => [['item_id' => $item->id, 'is_mythic' => false]], 'game_map_bonus' => 0, 'looting_chance' => 0]],
        ]);
        $this->instance(SkillCheckService::class, Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getDCCheck')->andReturn(1);
            $mock->shouldReceive('characterRoll')->andReturn(100);
        }));
        Event::listen(UpdateCharacterInventoryCountEvent::class, function (): void {
            throw new BroadcastException('The broadcast server could not be reached.');
        });

        resolve(BattleRewardService::class)->processLedgerAwareRewards($request);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Inventory count update after disenchant failed.', Mockery::on(
                fn (array $context): bool => $context['character_id'] === $character->id
                    && $context['disenchant_outcome'] === 'disenchanted',
            ));
    }
}
