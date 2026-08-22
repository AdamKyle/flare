<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Admin\Events\BatchCraftingMonitoringUpdated;
use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingOrchestrator;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingStatus;
use App\Game\Automation\BatchCrafting\Events\BatchCraftingStatusUpdated;
use App\Game\Automation\BatchCrafting\Factories\BatchCraftingOrchestratorFactory;
use App\Game\Automation\BatchCrafting\Jobs\BatchCraftingJob;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingAutomationService;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class BatchCraftingAutomationProcessingTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateItem, RefreshDatabase;

    private ?GameSkill $weaponCrafting;

    private ?Character $character;

    private ?BatchCraftingAutomationService $service;

    private array $progress;

    protected function setUp(): void
    {
        parent::setUp();

        $this->weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($this->weaponCrafting, 10, false)->getCharacter();
        $this->character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->service = resolve(BatchCraftingAutomationService::class);
        $this->progress = [
            'craft_mode' => 'specific_item',
            'specific_crafting_type' => 'dagger',
            'specific_item_id' => 1,
            'craft_amount' => 1,
            'craft_specific_count' => 0,
            'scheduled_for' => null,
            'processing_started_at' => null,
            'gold_spent_total' => 0,
            'gold_gained_total' => 0,
            'chart_points' => [],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->weaponCrafting = null;
        $this->character = null;
        $this->service = null;
    }

    public function test_process_applies_the_additional_sold_count_when_a_keep_best_displacement_occurs(): void
    {
        Queue::fake();
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $strongDagger = $this->createItem(['name' => 'Strong Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 5, 'skill_level_trivial' => 50]);
        $previousBestItem = $this->createItem(['name' => 'Previous Best Dagger', 'type' => 'dagger', 'skill_level_required' => 1]);
        $placement = resolve(BatchCraftingSetService::class)->createItemInBatchCraftingSet($this->character, $previousBestItem);
        $this->character->update(['gold' => 100000]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => [
                'craft_mode' => 'experience',
                'cycle_position' => 0,
                'crafting_xp_gained' => 0,
                'kept_best' => ['dagger' => ['item_id' => $previousBestItem->id, 'set_slot_id' => $placement['set_slot']->id, 'quality' => 1]],
                'scheduled_for' => null,
                'processing_started_at' => null,
                'gold_spent_total' => 0,
                'gold_gained_total' => 0,
                'chart_points' => [],
            ],
        ]);

        $this->service->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(6, $batchCrafting->crafted_count);
        $this->assertSame(6, $batchCrafting->kept_count);
        $this->assertSame(1, $batchCrafting->sold_count);
    }

    public function test_process_applies_the_additional_destroyed_count_when_a_keep_best_displacement_occurs(): void
    {
        Queue::fake();
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $strongDagger = $this->createItem(['name' => 'Strong Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 5, 'skill_level_trivial' => 50]);
        $previousBestItem = $this->createItem(['name' => 'Previous Best Dagger', 'type' => 'dagger', 'skill_level_required' => 1]);
        $placement = resolve(BatchCraftingSetService::class)->createItemInBatchCraftingSet($this->character, $previousBestItem);
        $this->character->update(['gold' => 100000]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => [
                'craft_mode' => 'experience',
                'cycle_position' => 0,
                'crafting_xp_gained' => 0,
                'kept_best' => ['dagger' => ['item_id' => $previousBestItem->id, 'set_slot_id' => $placement['set_slot']->id, 'quality' => 1]],
                'scheduled_for' => null,
                'processing_started_at' => null,
                'gold_spent_total' => 0,
                'gold_gained_total' => 0,
                'chart_points' => [],
            ],
        ]);

        $this->service->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(6, $batchCrafting->crafted_count);
        $this->assertSame(6, $batchCrafting->kept_count);
        $this->assertSame(1, $batchCrafting->destroyed_count);
    }

    public function test_process_completes_multiple_requested_items_in_one_job_run(): void
    {
        Queue::fake();
        $item = $this->createItem(['name' => 'Continuous Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_amount' => 3],
        ]);

        $this->service->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(3, $batchCrafting->crafted_count);
        $this->assertSame(3, $batchCrafting->progress['craft_specific_count']);
        $this->assertSame(BatchCraftingStatus::COMPLETED->value, $batchCrafting->status);
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $batchCrafting->ended_reason);
        $this->assertNotNull($batchCrafting->progress['processing_started_at']);
        $this->assertCount(3, $batchCrafting->progress['chart_points']);
        $this->assertSame(3, $batchCrafting->progress['chart_points'][2]['successful']);
        Queue::assertNotPushed(BatchCraftingJob::class);
    }

    public function test_process_continues_after_a_failed_roll_and_still_reaches_the_requested_amount(): void
    {
        $item = $this->createItem(['name' => 'Roll Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 20]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_amount' => 3],
        ]);
        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(40);
                $mock->shouldReceive('characterRoll')->andReturn(1, 50, 50, 50);
            })
        );

        resolve(BatchCraftingAutomationService::class)->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(3, $batchCrafting->crafted_count);
        $this->assertSame(1, $batchCrafting->failed_count);
        $this->assertSame(BatchCraftingStatus::COMPLETED->value, $batchCrafting->status);
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $batchCrafting->ended_reason);
        $this->assertCount(4, $batchCrafting->progress['chart_points']);
        $finalPoint = $batchCrafting->progress['chart_points'][3];
        $this->assertSame(3, $finalPoint['successful']);
        $this->assertSame(1, $finalPoint['failed']);
        $this->assertSame(4, $batchCrafting->progress['gold_spent_total']);
        $this->assertSame(0, $batchCrafting->progress['gold_gained_total']);
    }

    public function test_process_records_cumulative_gold_spent_and_gold_gained_for_sell_disposition(): void
    {
        $item = $this->createItem(['name' => 'Sale Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 100, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_amount' => 2],
        ]);
        $goldBefore = $this->character->gold;
        $expectedSalePrice = max(0, SellItemCalculator::fetchSalePriceWithAffixes($item));

        $this->service->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(2, $batchCrafting->crafted_count);
        $this->assertSame(200, $batchCrafting->progress['gold_spent_total']);
        $this->assertSame($expectedSalePrice * 2, $batchCrafting->progress['gold_gained_total']);
        $this->assertSame($goldBefore - 200 + ($expectedSalePrice * 2), $this->character->refresh()->gold);
        $lastPoint = $batchCrafting->progress['chart_points'][1];
        $this->assertSame(200, $lastPoint['gold_spent']);
        $this->assertSame($expectedSalePrice * 2, $lastPoint['gold_gained']);
    }

    public function test_process_completes_failed_when_the_crafted_items_set_destination_fails_at_commit_time(): void
    {
        $item = $this->createItem(['name' => 'Commit Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'output_destination' => 'crafted_items_set'],
        ]);
        $batchCraftingSetService = Mockery::mock(BatchCraftingSetService::class, function (MockInterface $mock) {
            $mock->shouldReceive('canAccept')->once()->andReturn(true);
            $mock->shouldReceive('createItemInBatchCraftingSet')->once()->andReturn(['success' => false, 'reason' => 'set_full', 'set_slot' => null]);
            $mock->shouldReceive('findBatchCraftingSet')->andReturn(null);
        });
        $this->app->instance(BatchCraftingSetService::class, $batchCraftingSetService);

        resolve(BatchCraftingAutomationService::class)->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(0, $batchCrafting->crafted_count);
        $this->assertSame(1, $batchCrafting->failed_count);
        $this->assertSame(BatchCraftingEndReason::FAILED->value, $batchCrafting->ended_reason);
        $this->assertCount(1, $batchCrafting->progress['chart_points']);
        $this->assertSame(10, $batchCrafting->progress['chart_points'][0]['gold_spent']);
    }

    public function test_process_completes_with_no_gold_and_preserves_earlier_chart_points(): void
    {
        $item = $this->createItem(['name' => 'Exact Gold Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 100, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->character->update(['gold' => 100]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_amount' => 2],
        ]);

        $this->service->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(1, $batchCrafting->crafted_count);
        $this->assertSame(BatchCraftingEndReason::NO_GOLD->value, $batchCrafting->ended_reason);
        $this->assertCount(1, $batchCrafting->progress['chart_points']);
        $this->assertSame(100, $batchCrafting->progress['gold_spent_total']);
    }

    public function test_process_does_nothing_when_the_batch_was_cancelled_before_it_could_run(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $item = $this->createItem(['name' => 'Scheduled Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_amount' => 3],
        ]);
        $this->service->cancel($this->character);
        Event::fake([BatchCraftingStatusUpdated::class]);

        $this->service->process($batchCrafting);

        $this->assertSame(0, $batchCrafting->refresh()->crafted_count);
        $this->assertSame(BatchCraftingEndReason::CANCELLED->value, $batchCrafting->ended_reason);
        Event::assertNotDispatched(BatchCraftingStatusUpdated::class);
    }

    public function test_process_does_not_rebroadcast_processing_started_when_already_marked(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $item = $this->createItem(['name' => 'Already Processing Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'processing_started_at' => now()->subMinute()->toJSON()],
        ]);

        $this->service->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(1, $batchCrafting->crafted_count);
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $batchCrafting->ended_reason);
        Event::assertDispatchedTimes(BatchCraftingStatusUpdated::class, 1);
    }

    public function test_process_stops_between_attempts_when_the_batch_is_cancelled_externally_mid_run(): void
    {
        $item = $this->createItem(['name' => 'Mid Run Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_amount' => 3],
        ]);
        $orchestrator = Mockery::mock(BatchCraftingOrchestrator::class);
        $orchestrator->shouldReceive('orchestrate')->once()->andReturnUsing(function () use ($batchCrafting) {
            BatchCrafting::whereKey($batchCrafting->id)->update([
                'completed_at' => now(),
                'cancelled_at' => now(),
                'ended_reason' => BatchCraftingEndReason::CANCELLED->value,
            ]);

            return BatchCraftingOperationResult::destroyed(1);
        });
        $orchestratorFactory = Mockery::mock(BatchCraftingOrchestratorFactory::class, function (MockInterface $mock) use ($orchestrator) {
            $mock->shouldReceive('make')->once()->andReturn($orchestrator);
        });
        $this->app->instance(BatchCraftingOrchestratorFactory::class, $orchestratorFactory);

        resolve(BatchCraftingAutomationService::class)->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(1, $batchCrafting->crafted_count);
        $this->assertSame(BatchCraftingEndReason::CANCELLED->value, $batchCrafting->ended_reason);
    }

    public function test_process_increments_applied_count_for_an_applied_result(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => 1],
        ]);
        $orchestrator = Mockery::mock(BatchCraftingOrchestrator::class);
        $orchestrator->shouldReceive('orchestrate')->once()->andReturn(
            BatchCraftingOperationResult::applied(10)->withEndReason(BatchCraftingEndReason::EVENT_GOAL_COMPLETE)
        );
        $orchestratorFactory = Mockery::mock(BatchCraftingOrchestratorFactory::class, function (MockInterface $mock) use ($orchestrator) {
            $mock->shouldReceive('make')->once()->andReturn($orchestrator);
        });
        $this->app->instance(BatchCraftingOrchestratorFactory::class, $orchestratorFactory);

        resolve(BatchCraftingAutomationService::class)->process($batchCrafting);

        $this->assertSame(1, $batchCrafting->refresh()->applied_count);
    }

    public function test_process_increments_the_disenchanted_progress_counter_for_a_disenchanted_result(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
            'progress' => [...$this->progress, 'specific_item_id' => 1],
        ]);
        $orchestrator = Mockery::mock(BatchCraftingOrchestrator::class);
        $orchestrator->shouldReceive('orchestrate')->once()->andReturn(
            BatchCraftingOperationResult::disenchanted(10)->withEndReason(BatchCraftingEndReason::AMOUNT_REACHED)
        );
        $orchestratorFactory = Mockery::mock(BatchCraftingOrchestratorFactory::class, function (MockInterface $mock) use ($orchestrator) {
            $mock->shouldReceive('make')->once()->andReturn($orchestrator);
        });
        $this->app->instance(BatchCraftingOrchestratorFactory::class, $orchestratorFactory);

        resolve(BatchCraftingAutomationService::class)->process($batchCrafting);

        $refreshed = $batchCrafting->refresh();
        $this->assertSame(1, $refreshed->crafted_count);
        $this->assertSame(1, $refreshed->progress['disenchanted_count']);
    }

    public function test_process_increments_the_disenchanted_progress_counter_for_an_additional_disenchant_displacement(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST->value,
            'progress' => [...$this->progress, 'specific_item_id' => 1],
        ]);
        $orchestrator = Mockery::mock(BatchCraftingOrchestrator::class);
        $orchestrator->shouldReceive('orchestrate')->once()->andReturn(
            BatchCraftingOperationResult::keptWithDisplacedDisenchant(10)->withEndReason(BatchCraftingEndReason::AMOUNT_REACHED)
        );
        $orchestratorFactory = Mockery::mock(BatchCraftingOrchestratorFactory::class, function (MockInterface $mock) use ($orchestrator) {
            $mock->shouldReceive('make')->once()->andReturn($orchestrator);
        });
        $this->app->instance(BatchCraftingOrchestratorFactory::class, $orchestratorFactory);

        resolve(BatchCraftingAutomationService::class)->process($batchCrafting);

        $refreshed = $batchCrafting->refresh();
        $this->assertSame(1, $refreshed->kept_count);
        $this->assertSame(1, $refreshed->progress['disenchanted_count']);
    }

    public function test_process_increments_the_used_progress_counter_for_a_used_result(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::USE_NOW->value,
            'progress' => [...$this->progress, 'specific_item_id' => 1],
        ]);
        $orchestrator = Mockery::mock(BatchCraftingOrchestrator::class);
        $orchestrator->shouldReceive('orchestrate')->once()->andReturn(
            BatchCraftingOperationResult::used()->withEndReason(BatchCraftingEndReason::AMOUNT_REACHED)
        );
        $orchestratorFactory = Mockery::mock(BatchCraftingOrchestratorFactory::class, function (MockInterface $mock) use ($orchestrator) {
            $mock->shouldReceive('make')->once()->andReturn($orchestrator);
        });
        $this->app->instance(BatchCraftingOrchestratorFactory::class, $orchestratorFactory);

        resolve(BatchCraftingAutomationService::class)->process($batchCrafting);

        $refreshed = $batchCrafting->refresh();
        $this->assertSame(1, $refreshed->crafted_count);
        $this->assertSame(1, $refreshed->progress['used_count']);
    }

    public function test_process_fires_status_and_monitoring_events_for_processing_start_and_completion(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class, BatchCraftingMonitoringUpdated::class]);
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id],
        ]);

        $this->service->process($batchCrafting);

        Event::assertDispatchedTimes(BatchCraftingStatusUpdated::class, 2);
        Event::assertDispatchedTimes(BatchCraftingMonitoringUpdated::class, 2);
    }

    public function test_process_returns_immediately_when_the_batch_no_longer_exists(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $this->character->id, 'user_id' => $this->character->user_id]);
        $batchCrafting->delete();

        $this->service->process($batchCrafting);

        Event::assertNotDispatched(BatchCraftingStatusUpdated::class);
    }

    public function test_process_completes_with_amount_reached_when_target_already_met(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_specific_count' => 1],
        ]);

        $this->service->process($batchCrafting);

        $this->assertFalse($batchCrafting->refresh()->isRunning());
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $batchCrafting->ended_reason);
    }

    public function test_process_completes_with_no_inventory_space_when_inventory_destination_is_full(): void
    {
        $this->character->update(['inventory_max' => 0]);
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'output_destination' => 'inventory'],
        ]);

        $this->service->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_INVENTORY_SPACE->value, $batchCrafting->refresh()->ended_reason);
    }

    public function test_process_completes_with_batch_crafting_set_full_when_crafted_items_set_is_full(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => 0]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'output_destination' => 'crafted_items_set'],
        ]);

        $this->service->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $batchCrafting->refresh()->ended_reason);
    }

    public function test_process_completes_with_died_when_character_is_dead(): void
    {
        $this->character->update(['is_dead' => true]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => $this->progress,
        ]);

        $this->service->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::DIED->value, $batchCrafting->refresh()->ended_reason);
    }

    public function test_process_completes_with_completed_duration_when_the_eight_hour_timeout_has_passed(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => $this->progress,
            'ends_at' => now()->subMinute(),
        ]);

        $this->service->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::COMPLETED_DURATION->value, $batchCrafting->refresh()->ended_reason);
    }

    public function test_process_does_nothing_when_batch_was_already_completed_before_it_ran(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $this->character->id, 'user_id' => $this->character->user_id]);
        $batchCrafting->update(['completed_at' => now(), 'cancelled_at' => now(), 'ended_reason' => BatchCraftingEndReason::CANCELLED->value]);

        $this->service->process($batchCrafting);

        Event::assertNotDispatched(BatchCraftingStatusUpdated::class);
    }

    public function test_process_reports_and_completes_failed_on_unexpected_throwable(): void
    {
        Log::spy();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => $this->progress,
        ]);
        $orchestratorFactory = Mockery::mock(BatchCraftingOrchestratorFactory::class, function (MockInterface $mock) {
            $mock->shouldReceive('make')->andThrow(new RuntimeException('Unexpected crafting failure.'));
        });
        $this->app->instance(BatchCraftingOrchestratorFactory::class, $orchestratorFactory);
        $monitoredBugReportService = Mockery::mock(MonitoredBugReportService::class, function (MockInterface $mock) {
            $mock->shouldReceive('reportError')->once();
        });
        $this->app->instance(MonitoredBugReportService::class, $monitoredBugReportService);

        resolve(BatchCraftingAutomationService::class)->process($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(BatchCraftingStatus::COMPLETED->value, $batchCrafting->status);
        $this->assertSame(BatchCraftingEndReason::FAILED->value, $batchCrafting->ended_reason);
        Log::shouldHaveReceived('error')->once()->withArgs(function (string $message, array $context) use ($batchCrafting) {
            return $message === 'Batch Crafting operation failed.' && $context['batch_crafting_id'] === $batchCrafting->id;
        });
    }
}
