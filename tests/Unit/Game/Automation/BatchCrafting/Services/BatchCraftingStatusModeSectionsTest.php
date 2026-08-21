<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingStatusService;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

class BatchCraftingStatusModeSectionsTest extends TestCase
{
    use CreateBatchCrafting, CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalEventGoal, CreateInventorySets, CreateItem, CreateScheduledEvent, RefreshDatabase;

    private ?BatchCraftingStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(BatchCraftingStatusService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_build_reports_set_progress_for_a_craft_set_batch(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['name' => 'Status Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $queue = [
            ['position' => 'body', 'item_id' => $item->id, 'crafting_type' => 'armour', 'item_name' => $item->name],
            ['position' => 'leggings', 'item_id' => $item->id, 'crafting_type' => 'armour', 'item_name' => $item->name],
        ];
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'craft_set', 'set_queue' => $queue, 'set_index' => 1, 'current_item_id' => $item->id, 'current_item_name' => $item->name, 'current_crafting_type' => 'armour', 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);

        $result = $this->service->build($character);

        $this->assertSame(2, $result['batch']['set_progress']['total_entries']);
        $this->assertSame(1, $result['batch']['set_progress']['completed_entries']);
        $this->assertSame(1, $result['batch']['set_progress']['remaining_entries']);
        $this->assertSame('leggings', $result['batch']['set_progress']['current_position']);
        $this->assertSame($item->id, $result['batch']['current_item_id']);
        $this->assertNull($result['batch']['requested_amount']);
    }

    public function test_build_reports_inventory_set_destination_capacity_for_a_craft_set_batch(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $targetSet = $this->createInventorySet(['character_id' => $character->id, 'name' => 'Destination Set', 'max_slots' => null]);
        $item = $this->createItem(['name' => 'Status Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'set_queue' => [['position' => 'body', 'item_id' => $item->id, 'crafting_type' => 'armour', 'item_name' => $item->name]], 'set_index' => 0, 'output_destination' => 'inventory_set', 'output_set_id' => $targetSet->id, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);

        $result = $this->service->build($character);

        $this->assertSame($targetSet->id, $result['batch']['destination_set_id']);
        $this->assertSame('Destination Set', $result['batch']['destination_set_name']);
        $this->assertNull($result['batch']['destination_capacity']);
    }

    public function test_build_reports_null_destination_when_the_selected_inventory_set_no_longer_exists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['name' => 'Status Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'set_queue' => [['position' => 'body', 'item_id' => $item->id, 'crafting_type' => 'armour', 'item_name' => $item->name]], 'set_index' => 0, 'output_destination' => 'inventory_set', 'output_set_id' => 999999, 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);

        $result = $this->service->build($character);

        $this->assertNull($result['batch']['destination_set_id']);
        $this->assertNull($result['batch']['destination_capacity']);
    }

    public function test_build_reports_experience_progress_for_a_craft_experience_batch(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 4, 'crafting_xp_gained' => 75, 'current_item_id' => 5, 'current_item_name' => 'Test Dagger', 'current_crafting_type' => 'weapon', 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);

        $result = $this->service->build($character);

        $this->assertSame(6, $result['batch']['experience_progress']['actions_per_minute']);
        $this->assertSame(4, $result['batch']['experience_progress']['current_cycle_position']);
        $this->assertSame(23, $result['batch']['experience_progress']['cycle_size']);
        $this->assertSame(75, $result['batch']['experience_progress']['crafting_xp_gained']);
        $this->assertNotEmpty($result['batch']['experience_progress']['crafting_skills']);
        $this->assertSame('Test Dagger', $result['batch']['current_item_name']);
    }

    public function test_build_reports_event_progress_for_a_craft_event_batch(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);
        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 500,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);
        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'event', 'event_goal_id' => $goal->id, 'event_cycle_position' => 2, 'crafting_xp_gained' => 40, 'current_crafting_type' => 'ring', 'scheduled_for' => null, 'processing_started_at' => null, 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
            'skipped_count' => 3,
        ]);

        $result = $this->service->build($character);

        $this->assertSame(23, $result['batch']['event_progress']['actions_per_minute']);
        $this->assertSame('ring', $result['batch']['event_progress']['current_crafting_type']);
        $this->assertSame(3, $result['batch']['event_progress']['skipped_count']);
        $this->assertSame(40, $result['batch']['event_progress']['crafting_xp_gained']);
        $this->assertSame($goal->id, $result['batch']['event_progress']['goal_id']);
        $this->assertSame(500, $result['batch']['event_progress']['max_crafts']);
    }

    public function test_build_reports_capabilities_even_when_no_batch_is_visible(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->build($character);

        $this->assertArrayHasKey('capabilities', $result);
        $this->assertFalse($result['capabilities']['can_craft_for_experience']);
        $this->assertFalse($result['capabilities']['can_craft_for_event']);
    }

    public function test_build_reports_a_waiting_batch_state(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'scheduled_for' => null, 'processing_started_at' => now()->toJSON(), 'next_attempt_at' => now()->addMinute()->toJSON(), 'gold_spent_total' => 0, 'gold_gained_total' => 0, 'chart_points' => []],
        ]);

        $result = $this->service->build($character);

        $this->assertTrue($result['is_waiting']);
        $this->assertFalse($result['is_processing']);
        $this->assertFalse($result['is_scheduled']);
    }
}
