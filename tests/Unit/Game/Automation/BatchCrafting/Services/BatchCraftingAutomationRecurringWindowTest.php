<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\BatchCrafting;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingOrchestrator;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Factories\BatchCraftingOrchestratorFactory;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingAutomationService;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingStatusService;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateScheduledEvent;

class BatchCraftingAutomationRecurringWindowTest extends TestCase
{
    use CreateBatchCrafting, CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalEventGoal, CreateItem, CreateScheduledEvent, RefreshDatabase;

    private ?BatchCraftingAutomationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(BatchCraftingAutomationService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_experience_process_performs_exactly_six_actions_in_one_window_and_schedules_the_next_minute(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Recurring Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 500]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);

        $nextAttemptAt = $this->service->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertNotNull($nextAttemptAt);
        $this->assertEqualsWithDelta(now()->addMinute()->timestamp, $nextAttemptAt->timestamp, 5);
        $this->assertCount(6, $batchCrafting->progress['chart_points']);
        $this->assertSame($nextAttemptAt->toJSON(), $batchCrafting->progress['next_attempt_at']);
        $this->assertTrue($batchCrafting->isRunning());
        $this->assertNotNull($batchCrafting->progress['processing_started_at']);
    }

    public function test_experience_next_window_preserves_processing_started_at_and_continues_the_cycle(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Recurring Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 500]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);
        $this->service->process($batchCrafting);
        $batchCrafting->refresh();
        $firstProcessingStartedAt = $batchCrafting->progress['processing_started_at'];

        $secondNextAttemptAt = $this->service->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame($firstProcessingStartedAt, $batchCrafting->progress['processing_started_at']);
        $this->assertNotNull($secondNextAttemptAt);
        $this->assertCount(12, $batchCrafting->progress['chart_points']);
    }

    public function test_experience_cycle_position_persists_across_windows_instead_of_restarting(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);

        foreach (ItemType::validWeapons() as $weaponType) {
            $this->createItem(['name' => 'Cycle '.$weaponType, 'type' => $weaponType, 'crafting_type' => 'weapon', 'default_position' => $weaponType, 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 500]);
        }

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);

        $this->service->process($batchCrafting);
        $cyclePositionAfterFirstWindow = $batchCrafting->fresh()->progress['cycle_position'];
        $this->assertSame(6, $cyclePositionAfterFirstWindow);

        $this->service->process($batchCrafting);

        $this->assertSame(12, $batchCrafting->fresh()->progress['cycle_position']);
    }

    public function test_experience_process_reports_the_waiting_state_after_a_window_completes(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Recurring Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 500]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);

        $this->service->process($batchCrafting);

        $snapshot = resolve(BatchCraftingStatusService::class)->build($character);
        $this->assertTrue($snapshot['is_waiting']);
        $this->assertFalse($snapshot['is_processing']);
    }

    public function test_event_process_performs_exactly_twenty_three_action_slots_in_one_window(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);
        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100000,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);
        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->assignSkill($skill, 10, false)->getCharacter();
        $character->update(['gold' => 1000000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Event Window Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 500]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'event', 'event_cycle_position' => 0, 'crafting_xp_gained' => 0, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);

        $nextAttemptAt = $this->service->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertNotNull($nextAttemptAt);
        $this->assertTrue($batchCrafting->isRunning());
        $this->assertSame(3, $batchCrafting->progress['event_cycle_position']);
    }

    public function test_craft_and_enchant_experience_process_performs_exactly_twenty_three_actions_in_one_window(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_enchant_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'enchanting_xp_gained' => 0, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);
        $callCount = 0;
        $orchestrator = Mockery::mock(BatchCraftingOrchestrator::class);
        $orchestrator->shouldReceive('orchestrate')->times(23)->andReturnUsing(function () use (&$callCount): BatchCraftingOperationResult {
            $callCount++;

            return BatchCraftingOperationResult::crafted(0);
        });
        $factory = Mockery::mock(BatchCraftingOrchestratorFactory::class);
        $factory->shouldReceive('make')->andReturn($orchestrator);
        $this->app->instance(BatchCraftingOrchestratorFactory::class, $factory);

        $nextAttemptAt = resolve(BatchCraftingAutomationService::class)->process($batchCrafting);

        $this->assertNotNull($nextAttemptAt);
        $this->assertSame(23, $callCount);
    }

    public function test_enchant_event_process_performs_exactly_twenty_three_actions_in_one_window(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['enchant_mode' => 'event', 'event_enchant_phase' => 'select', 'enchanting_xp_gained' => 0, 'crafting_xp_gained' => 0, 'event_goal_id' => null, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);
        $callCount = 0;
        $orchestrator = Mockery::mock(BatchCraftingOrchestrator::class);
        $orchestrator->shouldReceive('orchestrate')->times(23)->andReturnUsing(function () use (&$callCount): BatchCraftingOperationResult {
            $callCount++;

            return BatchCraftingOperationResult::crafted(0);
        });
        $factory = Mockery::mock(BatchCraftingOrchestratorFactory::class);
        $factory->shouldReceive('make')->andReturn($orchestrator);
        $this->app->instance(BatchCraftingOrchestratorFactory::class, $factory);

        $nextAttemptAt = resolve(BatchCraftingAutomationService::class)->process($batchCrafting);

        $this->assertNotNull($nextAttemptAt);
        $this->assertSame(23, $callCount);
    }

    public function test_alchemy_experience_process_performs_exactly_six_actions_in_one_window(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['alchemy_mode' => 'experience', 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);
        $callCount = 0;
        $orchestrator = Mockery::mock(BatchCraftingOrchestrator::class);
        $orchestrator->shouldReceive('orchestrate')->times(6)->andReturnUsing(function () use (&$callCount): BatchCraftingOperationResult {
            $callCount++;

            return BatchCraftingOperationResult::crafted(0);
        });
        $factory = Mockery::mock(BatchCraftingOrchestratorFactory::class);
        $factory->shouldReceive('make')->andReturn($orchestrator);
        $this->app->instance(BatchCraftingOrchestratorFactory::class, $factory);

        $nextAttemptAt = resolve(BatchCraftingAutomationService::class)->process($batchCrafting);

        $this->assertNotNull($nextAttemptAt);
        $this->assertSame(6, $callCount);
    }

    public function test_trinketry_process_performs_exactly_six_actions_in_one_window(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['trinketry_mode' => 'experience', 'trinketry_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);
        $callCount = 0;
        $orchestrator = Mockery::mock(BatchCraftingOrchestrator::class);
        $orchestrator->shouldReceive('orchestrate')->times(6)->andReturnUsing(function () use (&$callCount): BatchCraftingOperationResult {
            $callCount++;

            return BatchCraftingOperationResult::crafted(0);
        });
        $factory = Mockery::mock(BatchCraftingOrchestratorFactory::class);
        $factory->shouldReceive('make')->andReturn($orchestrator);
        $this->app->instance(BatchCraftingOrchestratorFactory::class, $factory);

        $nextAttemptAt = resolve(BatchCraftingAutomationService::class)->process($batchCrafting);

        $this->assertNotNull($nextAttemptAt);
        $this->assertSame(6, $callCount);
    }

    public function test_continuous_modes_are_not_capped_by_a_fixed_recurring_window(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 999, 'completed_amount' => 0, 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);
        $callCount = 0;
        $orchestrator = Mockery::mock(BatchCraftingOrchestrator::class);
        $orchestrator->shouldReceive('orchestrate')->times(30)->andReturnUsing(function (BatchCrafting $orchestrated) use (&$callCount): BatchCraftingOperationResult {
            $callCount++;

            if ($callCount === 30) {
                $orchestrated->update(['cancelled_at' => now()]);
            }

            return BatchCraftingOperationResult::crafted(0);
        });
        $factory = Mockery::mock(BatchCraftingOrchestratorFactory::class);
        $factory->shouldReceive('make')->andReturn($orchestrator);
        $this->app->instance(BatchCraftingOrchestratorFactory::class, $factory);

        $nextAttemptAt = resolve(BatchCraftingAutomationService::class)->process($batchCrafting);

        $this->assertNull($nextAttemptAt);
        $this->assertSame(30, $callCount);
    }

    public function test_process_returns_null_when_the_batch_is_cancelled_during_the_final_window_operation(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);
        $callCount = 0;
        $orchestrator = Mockery::mock(BatchCraftingOrchestrator::class);
        $orchestrator->shouldReceive('orchestrate')->times(6)->andReturnUsing(function (BatchCrafting $orchestrated) use (&$callCount): BatchCraftingOperationResult {
            $callCount++;

            if ($callCount === 6) {
                $orchestrated->update(['cancelled_at' => now()]);
            }

            return BatchCraftingOperationResult::crafted(0);
        });
        $factory = Mockery::mock(BatchCraftingOrchestratorFactory::class);
        $factory->shouldReceive('make')->andReturn($orchestrator);
        $this->app->instance(BatchCraftingOrchestratorFactory::class, $factory);

        $nextAttemptAt = resolve(BatchCraftingAutomationService::class)->process($batchCrafting);

        $this->assertNull($nextAttemptAt);
    }
}
