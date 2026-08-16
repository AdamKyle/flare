<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Admin\Events\BatchCraftingMonitoringUpdated;
use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingStatus;
use App\Game\Automation\BatchCrafting\Events\BatchCraftingStatusUpdated;
use App\Game\Automation\BatchCrafting\Factories\BatchCraftingOrchestratorFactory;
use App\Game\Automation\BatchCrafting\Jobs\BatchCraftingJob;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingAutomationService;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($this->weaponCrafting, 10, false)->getCharacter();
        $this->character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->service = resolve(BatchCraftingAutomationService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->weaponCrafting = null;
        $this->character = null;
        $this->service = null;
    }

    public function test_process_one_operation_increments_crafted_and_kept_counts_for_a_kept_result(): void
    {
        Queue::fake();
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'craft_specific_count' => 0, 'output_destination' => 'crafted_items_set'],
        ]);

        $this->service->processOneOperation($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(1, $batchCrafting->crafted_count);
        $this->assertSame(1, $batchCrafting->kept_count);
    }

    public function test_process_one_operation_increments_crafted_and_sold_counts_for_a_sold_result(): void
    {
        Queue::fake();
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'craft_specific_count' => 0],
        ]);

        $this->service->processOneOperation($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(1, $batchCrafting->crafted_count);
        $this->assertSame(1, $batchCrafting->sold_count);
    }

    public function test_process_one_operation_increments_crafted_and_destroyed_counts_for_a_destroyed_result(): void
    {
        Queue::fake();
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'craft_specific_count' => 0],
        ]);

        $this->service->processOneOperation($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(1, $batchCrafting->crafted_count);
        $this->assertSame(1, $batchCrafting->destroyed_count);
    }

    public function test_process_one_operation_increments_failed_count_for_a_failed_craft(): void
    {
        Queue::fake();
        $item = $this->createItem(['name' => 'Roll Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 20]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'craft_specific_count' => 0],
        ]);
        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(100);
                $mock->shouldReceive('characterRoll')->once()->andReturn(1);
            })
        );

        resolve(BatchCraftingAutomationService::class)->processOneOperation($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(0, $batchCrafting->crafted_count);
        $this->assertSame(1, $batchCrafting->failed_count);
        $this->assertTrue($batchCrafting->isRunning());
    }

    public function test_process_one_operation_fires_one_status_event_and_one_monitoring_event(): void
    {
        Queue::fake();
        Event::fake([BatchCraftingStatusUpdated::class, BatchCraftingMonitoringUpdated::class]);
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'craft_specific_count' => 0],
        ]);

        $this->service->processOneOperation($batchCrafting);

        Event::assertDispatchedTimes(BatchCraftingStatusUpdated::class, 1);
        Event::assertDispatchedTimes(BatchCraftingMonitoringUpdated::class, 1);
    }

    public function test_process_one_operation_queues_the_next_job_one_minute_later_when_still_running(): void
    {
        Queue::fake();
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'craft_specific_count' => 0],
        ]);

        $this->service->processOneOperation($batchCrafting);

        Queue::assertPushed(BatchCraftingJob::class, function (BatchCraftingJob $job) use ($batchCrafting) {
            return $job->batchCraftingId === $batchCrafting->id && ! is_null($job->delay);
        });
    }

    public function test_process_one_operation_completes_immediately_after_the_final_successful_craft(): void
    {
        Queue::fake();
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0],
        ]);

        $this->service->processOneOperation($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(1, $batchCrafting->progress['craft_specific_count']);
        $this->assertSame(BatchCraftingStatus::COMPLETED->value, $batchCrafting->status);
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $batchCrafting->ended_reason);
        $this->assertNotNull($batchCrafting->completed_at);
        Queue::assertNotPushed(BatchCraftingJob::class);
    }

    public function test_process_one_operation_returns_immediately_when_the_batch_no_longer_exists(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $this->character->id, 'user_id' => $this->character->user_id]);
        $batchCrafting->delete();

        $this->service->processOneOperation($batchCrafting);

        Event::assertNotDispatched(BatchCraftingStatusUpdated::class);
    }

    public function test_process_one_operation_returns_immediately_when_no_longer_running(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'completed_at' => now(),
            'ended_reason' => BatchCraftingEndReason::CANCELLED->value,
        ]);

        $this->service->processOneOperation($batchCrafting);

        Event::assertNotDispatched(BatchCraftingStatusUpdated::class);
    }

    public function test_process_one_operation_completes_with_amount_reached_when_target_already_met(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 1],
        ]);

        $this->service->processOneOperation($batchCrafting);

        $this->assertFalse($batchCrafting->refresh()->isRunning());
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $batchCrafting->ended_reason);
    }

    public function test_process_one_operation_completes_with_no_gold_when_character_cannot_afford_the_craft(): void
    {
        $this->character->update(['gold' => 0]);
        $item = $this->createItem(['name' => 'Expensive Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 500, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0],
        ]);

        $this->service->processOneOperation($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_GOLD->value, $batchCrafting->refresh()->ended_reason);
    }

    public function test_process_one_operation_completes_with_no_inventory_space_when_inventory_destination_is_full(): void
    {
        $this->character->update(['inventory_max' => 0]);
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0, 'output_destination' => 'inventory'],
        ]);

        $this->service->processOneOperation($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_INVENTORY_SPACE->value, $batchCrafting->refresh()->ended_reason);
    }

    public function test_process_one_operation_completes_with_batch_crafting_set_full_when_crafted_items_set_is_full(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => 0]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0, 'output_destination' => 'crafted_items_set'],
        ]);

        $this->service->processOneOperation($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $batchCrafting->refresh()->ended_reason);
    }

    public function test_process_one_operation_completes_with_died_when_character_is_dead(): void
    {
        $this->character->update(['is_dead' => true]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => 1, 'craft_amount' => 1, 'craft_specific_count' => 0],
        ]);

        $this->service->processOneOperation($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::DIED->value, $batchCrafting->refresh()->ended_reason);
    }

    public function test_process_one_operation_completes_with_completed_duration_when_the_eight_hour_timeout_has_passed(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => 1, 'craft_amount' => 1, 'craft_specific_count' => 0],
            'ends_at' => now()->subMinute(),
        ]);

        $this->service->processOneOperation($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::COMPLETED_DURATION->value, $batchCrafting->refresh()->ended_reason);
    }

    public function test_process_one_operation_does_nothing_after_external_cancellation(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $this->character->id, 'user_id' => $this->character->user_id]);
        $batchCrafting->update(['completed_at' => now(), 'cancelled_at' => now(), 'ended_reason' => BatchCraftingEndReason::CANCELLED->value]);

        $this->service->processOneOperation($batchCrafting);

        Event::assertNotDispatched(BatchCraftingStatusUpdated::class);
    }

    public function test_process_one_operation_reports_and_completes_failed_on_unexpected_throwable(): void
    {
        Log::spy();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => 1, 'craft_amount' => 1, 'craft_specific_count' => 0],
        ]);
        $orchestratorFactory = Mockery::mock(BatchCraftingOrchestratorFactory::class, function (MockInterface $mock) {
            $mock->shouldReceive('make')->andThrow(new RuntimeException('Unexpected crafting failure.'));
        });
        $this->app->instance(BatchCraftingOrchestratorFactory::class, $orchestratorFactory);
        $monitoredBugReportService = Mockery::mock(MonitoredBugReportService::class, function (MockInterface $mock) {
            $mock->shouldReceive('reportError')->once();
        });
        $this->app->instance(MonitoredBugReportService::class, $monitoredBugReportService);

        resolve(BatchCraftingAutomationService::class)->processOneOperation($batchCrafting);

        $batchCrafting->refresh();
        $this->assertSame(BatchCraftingStatus::COMPLETED->value, $batchCrafting->status);
        $this->assertSame(BatchCraftingEndReason::FAILED->value, $batchCrafting->ended_reason);
        Log::shouldHaveReceived('error')->once()->withArgs(function (string $message, array $context) use ($batchCrafting) {
            return $message === 'Batch Crafting operation failed.' && $context['batch_crafting_id'] === $batchCrafting->id;
        });
    }
}
