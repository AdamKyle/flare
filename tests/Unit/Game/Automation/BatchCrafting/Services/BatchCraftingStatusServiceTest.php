<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySet;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingAutomationService;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingStatusService;
use App\Game\Automation\BatchCrafting\Services\Status\BatchCraftingStatusSectionResolver;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateScheduledEvent;

class BatchCraftingStatusServiceTest extends TestCase
{
    use CreateBatchCrafting, CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalEventGoal, CreateInventorySets, CreateItem, CreateScheduledEvent, RefreshDatabase;

    private ?GameSkill $weaponCrafting;

    private ?Character $character;

    private ?BatchCraftingStatusService $service;

    private array $progress;

    protected function setUp(): void
    {
        parent::setUp();

        $this->weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($this->weaponCrafting, 10, false)->getCharacter();
        $this->character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->service = resolve(BatchCraftingStatusService::class);
        $this->progress = [
            'craft_mode' => 'specific_item',
            'specific_crafting_type' => 'dagger',
            'specific_item_id' => 1,
            'craft_amount' => 5,
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

    public function test_build_reports_no_batch_when_nothing_exists(): void
    {
        $result = $this->service->build($this->character);

        $this->assertFalse($result['active']);
        $this->assertFalse($result['is_visible']);
        $this->assertNull($result['batch']);
    }

    public function test_build_reports_show_info_true_before_acknowledgement(): void
    {
        $result = $this->service->build($this->character);

        $this->assertTrue($result['show_info']);
    }

    public function test_build_reports_show_info_false_after_acknowledging(): void
    {
        resolve(BatchCraftingAutomationService::class)->acknowledgeInfo($this->character);

        $result = $this->service->build($this->character);

        $this->assertFalse($result['show_info']);
    }

    public function test_build_reports_a_scheduled_batch(): void
    {
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id],
        ]);

        $result = $this->service->build($this->character);

        $this->assertTrue($result['is_scheduled']);
        $this->assertFalse($result['is_processing']);
        $this->assertTrue($result['is_running']);
        $this->assertTrue($result['can_cancel']);
    }

    public function test_build_reports_a_processing_batch(): void
    {
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_specific_count' => 2, 'processing_started_at' => now()->toJSON()],
        ]);

        $result = $this->service->build($this->character);

        $this->assertFalse($result['is_scheduled']);
        $this->assertTrue($result['is_processing']);
        $this->assertTrue($result['is_running']);
    }

    public function test_build_reports_a_completed_visible_batch_as_dismissible(): void
    {
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'craft_specific_count' => 5],
            'completed_at' => now(),
            'ended_reason' => BatchCraftingEndReason::AMOUNT_REACHED->value,
        ]);

        $result = $this->service->build($this->character);

        $this->assertFalse($result['is_running']);
        $this->assertFalse($result['is_scheduled']);
        $this->assertFalse($result['is_processing']);
        $this->assertTrue($result['can_dismiss']);
        $this->assertFalse($result['can_cancel']);
    }

    public function test_build_reports_a_cancelled_visible_batch(): void
    {
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'craft_specific_count' => 2],
            'completed_at' => now(),
            'cancelled_at' => now(),
            'ended_reason' => BatchCraftingEndReason::CANCELLED->value,
        ]);

        $result = $this->service->build($this->character);

        $this->assertSame(BatchCraftingEndReason::CANCELLED->value, $result['batch']['ended_reason']);
        $this->assertTrue($result['can_dismiss']);
    }

    public function test_build_does_not_report_a_dismissed_batch(): void
    {
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'craft_specific_count' => 5],
            'completed_at' => now(),
            'ended_reason' => BatchCraftingEndReason::AMOUNT_REACHED->value,
            'panel_dismissed_at' => now(),
        ]);

        $result = $this->service->build($this->character);

        $this->assertFalse($result['is_visible']);
        $this->assertNull($result['batch']);
    }

    public function test_build_reports_current_item_id_and_name(): void
    {
        $item = $this->createItem(['name' => 'Rusted Blade', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id],
        ]);

        $result = $this->service->build($this->character);

        $this->assertSame($item->id, $result['batch']['current_item_id']);
        $this->assertSame('Rusted Blade', $result['batch']['current_item_name']);
    }

    public function test_build_reports_keep_inventory_capacity(): void
    {
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'output_destination' => 'inventory'],
        ]);

        $result = $this->service->build($this->character);

        $this->assertSame($this->character->getInventoryCount(), $result['batch']['destination_capacity']['current']);
        $this->assertSame(30, $result['batch']['destination_capacity']['max']);
    }

    public function test_build_reports_keep_crafted_items_set_capacity_and_identity_when_set_exists(): void
    {
        $set = $this->createInventorySet([
            'character_id' => $this->character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'max_slots' => 2000,
        ]);
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->fillInventorySetSlots($set, 3, $item->id);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'output_destination' => 'crafted_items_set'],
        ]);

        $result = $this->service->build($this->character);

        $this->assertSame(3, $result['batch']['destination_capacity']['current']);
        $this->assertSame(2000, $result['batch']['destination_capacity']['max']);
        $this->assertSame($set->id, $result['batch']['destination_set_id']);
        $this->assertSame(InventorySet::BATCH_CRAFTING_SET_NAME, $result['batch']['destination_set_name']);
    }

    public function test_build_reports_null_crafted_items_set_capacity_when_no_set_exists_yet(): void
    {
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'output_destination' => 'crafted_items_set'],
        ]);

        $result = $this->service->build($this->character);

        $this->assertNull($result['batch']['destination_capacity']);
        $this->assertNull($result['batch']['destination_set_id']);
        $this->assertNull($result['batch']['destination_set_name']);
        $this->assertSame(0, InventorySet::where('character_id', $this->character->id)->count());
    }

    public function test_build_reports_null_capacity_for_sell_disposition(): void
    {
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id],
        ]);

        $result = $this->service->build($this->character);

        $this->assertNull($result['batch']['destination_capacity']);
    }

    public function test_build_reports_null_capacity_for_destroy_disposition(): void
    {
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id],
        ]);

        $result = $this->service->build($this->character);

        $this->assertNull($result['batch']['destination_capacity']);
    }

    public function test_build_reports_requested_completed_and_remaining_amounts(): void
    {
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_specific_count' => 2],
        ]);

        $result = $this->service->build($this->character);

        $this->assertSame(5, $result['batch']['requested_amount']);
        $this->assertSame(2, $result['batch']['completed_amount']);
        $this->assertSame(3, $result['batch']['remaining_amount']);
    }

    public function test_build_reports_failed_count(): void
    {
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_specific_count' => 2],
            'failed_count' => 4,
        ]);

        $result = $this->service->build($this->character);

        $this->assertSame(4, $result['batch']['failed_count']);
    }

    public function test_build_reports_gold_spent_and_gold_gained_totals(): void
    {
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_specific_count' => 2, 'gold_spent_total' => 20, 'gold_gained_total' => 15],
        ]);

        $result = $this->service->build($this->character);

        $this->assertSame(20, $result['batch']['gold_spent']);
        $this->assertSame(15, $result['batch']['gold_gained']);
    }

    public function test_build_reports_the_characters_current_gold_as_gold_left(): void
    {
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_specific_count' => 2],
        ]);

        $result = $this->service->build($this->character);

        $this->assertSame(1000, $result['batch']['gold_left']);
    }

    public function test_build_reports_started_scheduled_processing_and_completed_timestamps(): void
    {
        $startedAt = now()->subMinutes(5);
        $scheduledFor = now()->subMinutes(4)->toJSON();
        $processingStartedAt = now()->subMinutes(3)->toJSON();
        $completedAt = now();
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'ended_reason' => BatchCraftingEndReason::AMOUNT_REACHED->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_specific_count' => 5, 'scheduled_for' => $scheduledFor, 'processing_started_at' => $processingStartedAt],
        ]);

        $result = $this->service->build($this->character);

        $this->assertSame($batchCrafting->refresh()->started_at->toJSON(), $result['batch']['started_at']);
        $this->assertSame($scheduledFor, $result['batch']['scheduled_for']);
        $this->assertSame($processingStartedAt, $result['batch']['processing_started_at']);
        $this->assertSame($batchCrafting->completed_at->toJSON(), $result['batch']['completed_at']);
    }

    public function test_build_returns_the_full_persisted_chart_history(): void
    {
        $chartPoints = [
            ['occurred_at' => now()->toJSON(), 'successful' => 1, 'failed' => 0, 'gold_spent' => 10, 'gold_gained' => 0],
            ['occurred_at' => now()->toJSON(), 'successful' => 2, 'failed' => 0, 'gold_spent' => 20, 'gold_gained' => 0],
        ];
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_specific_count' => 2, 'chart_points' => $chartPoints],
        ]);

        $result = $this->service->build($this->character);

        $this->assertCount(2, $result['batch']['chart_points']);
        $this->assertEquals($chartPoints[0], $result['batch']['chart_points'][0]);
        $this->assertEquals($chartPoints[1], $result['batch']['chart_points'][1]);
    }

    public function test_build_for_broadcast_returns_an_empty_chart_history(): void
    {
        $chartPoints = [
            ['occurred_at' => now()->toJSON(), 'successful' => 1, 'failed' => 0, 'gold_spent' => 10, 'gold_gained' => 0],
        ];
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_specific_count' => 2, 'chart_points' => $chartPoints],
        ]);

        $result = $this->service->buildForBroadcast($this->character);

        $this->assertSame([], $result['batch']['chart_points']);
    }

    public function test_build_for_broadcast_omits_general_capabilities_for_a_visible_batch(): void
    {
        $this->createItem(['name' => 'Meaningful Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id],
        ]);

        $result = $this->service->buildForBroadcast($this->character);

        $this->assertNull($result['capabilities']);
    }

    public function test_build_for_broadcast_recalculates_general_capabilities_when_no_batch_is_visible(): void
    {
        $this->createItem(['name' => 'Meaningful Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $result = $this->service->buildForBroadcast($this->character);

        $this->assertTrue($result['capabilities']['can_craft_for_experience']);
    }

    public function test_build_reports_true_capabilities_for_the_initial_get_even_with_a_visible_batch(): void
    {
        $this->createItem(['name' => 'Meaningful Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id],
        ]);

        $result = $this->service->build($this->character);

        $this->assertTrue($result['capabilities']['can_craft_for_experience']);
    }

    public function test_build_event_progress_preserves_final_goal_facts_after_the_goal_is_no_longer_currently_eligible(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::BATTLE,
            'ends_at' => now()->addHour(),
        ]);
        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);
        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();
        $this->createGlobalEventParticipation(['global_event_goal_id' => $goal->id, 'character_id' => $character->id, 'current_crafts' => 10]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'completed_at' => now(),
            'ended_reason' => BatchCraftingEndReason::EVENT_GOAL_COMPLETE->value,
            'progress' => ['craft_mode' => 'event', 'event_goal_id' => $goal->id, 'event_cycle_position' => 0, 'crafting_xp_gained' => 5, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);

        $result = $this->service->build($character);

        $this->assertTrue($result['is_visible']);
        $this->assertSame($goal->id, $result['batch']['event_progress']['goal_id']);
        $this->assertSame(10, $result['batch']['event_progress']['max_crafts']);
        $this->assertSame(10, $result['batch']['event_progress']['total_crafts']);
    }

    public function test_build_event_progress_reports_null_goal_facts_when_no_goal_was_ever_persisted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'completed_at' => now(),
            'ended_reason' => BatchCraftingEndReason::EVENT_NOT_RUNNING->value,
            'progress' => ['craft_mode' => 'event', 'event_goal_id' => null, 'event_cycle_position' => 0, 'crafting_xp_gained' => 0, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);

        $result = $this->service->build($character);

        $this->assertNull($result['batch']['event_progress']['goal_id']);
    }

    public function test_status_response_contains_no_backend_label_or_timer_fields(): void
    {
        $item = $this->createItem(['name' => 'Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [...$this->progress, 'specific_item_id' => $item->id, 'craft_specific_count' => 2],
        ]);

        $result = $this->service->build($this->character);

        $this->assertArrayNotHasKey('batch_label', $result['batch']);
        $this->assertArrayNotHasKey('human_mode_label', $result['batch']);
        $this->assertArrayNotHasKey('output_destination_label', $result['batch']);
        $this->assertArrayNotHasKey('elapsed_human', $result['batch']);
        $this->assertArrayNotHasKey('remaining_human', $result['batch']);
        $this->assertArrayNotHasKey('progress_percent', $result['batch']);
        $this->assertArrayNotHasKey('stop_reason', $result['batch']);
    }

    public function test_status_section_resolver_throws_clearly_when_no_section_is_registered_for_the_type_and_mode(): void
    {
        $resolver = new BatchCraftingStatusSectionResolver([]);

        $this->expectException(InvalidArgumentException::class);

        $resolver->resolve(BatchCraftingType::CRAFT, 'amount');
    }
}
