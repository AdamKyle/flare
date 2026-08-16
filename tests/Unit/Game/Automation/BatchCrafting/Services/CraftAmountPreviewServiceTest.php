<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\CraftAmountPreviewService;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class CraftAmountPreviewServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    private ?GameSkill $weaponCrafting;

    private ?Character $character;

    private ?CraftAmountPreviewService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($this->weaponCrafting, 10, false)->getCharacter();
        $this->character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->service = resolve(CraftAmountPreviewService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->weaponCrafting = null;
        $this->character = null;
        $this->service = null;
    }

    public function test_build_for_keep_to_inventory_returns_capacity_and_cost_details(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'inventory'],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertSame($item->id, $result['item']['id']);
        $this->assertSame('inventory', $result['output_destination']);
        $this->assertSame(30, $result['destination_capacity']['max']);
        $this->assertSame(0, $result['destination_capacity']['current']);
        $this->assertSame(30, $result['destination_capacity']['remaining']);
        $this->assertSame(50, $result['total_cost']);
        $this->assertTrue($result['can_afford']);
        $this->assertTrue($result['can_fit']);
        $this->assertSame([], $result['blockers']);
    }

    public function test_build_for_keep_to_crafted_items_set_returns_capacity_and_cost_details(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertSame('crafted_items_set', $result['output_destination']);
        $this->assertNotNull($result['destination_capacity']);
        $this->assertTrue($result['can_fit']);
    }

    public function test_build_for_sell_has_null_destination_capacity(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertNull($result['destination_capacity']);
        $this->assertNull($result['output_destination']);
        $this->assertSame([], $result['blockers']);
    }

    public function test_build_for_destroy_has_null_destination_capacity(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertNull($result['destination_capacity']);
        $this->assertSame([], $result['blockers']);
    }

    public function test_build_returns_blocker_for_unavailable_item(): void
    {
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => 999999, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertNull($result['item']);
        $this->assertSame(0, $result['maximum_request_amount']);
        $this->assertNotEmpty($result['blockers']);
    }

    public function test_build_flags_insufficient_gold(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->character->update(['gold' => 0]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertFalse($result['can_afford']);
        $this->assertSame(0, $result['maximum_request_amount']);
        $this->assertContains('You do not have enough Gold to craft this item.', $result['blockers']);
    }

    public function test_build_flags_when_inventory_destination_is_full(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->character->update(['inventory_max' => 0]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'inventory'],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertFalse($result['can_fit']);
        $this->assertSame(0, $result['destination_capacity']['remaining']);
        $this->assertNotEmpty($result['blockers']);
    }

    public function test_build_flags_when_crafted_items_set_destination_is_full(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => 0]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertFalse($result['can_fit']);
        $this->assertNotEmpty($result['blockers']);
    }

    public function test_build_maximum_request_amount_is_capped_by_gold_and_destination_capacity(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 100, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->character->update(['gold' => 250, 'inventory_max' => 30]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 30, 'output_destination' => 'inventory'],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertSame(2, $result['maximum_request_amount']);
    }

    public function test_build_zero_cost_item_can_afford_is_true(): void
    {
        $item = $this->createItem(['name' => 'Free Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertTrue($result['can_afford']);
        $this->assertSame(0, $result['total_cost']);
    }

    public function test_build_response_contains_only_the_lean_contract_keys(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $result = $this->service->build($this->character, $validated);

        $this->assertSame([
            'item', 'requested_amount', 'unit_cost', 'total_cost', 'available_gold', 'gold_after_purchase',
            'can_afford', 'output_destination', 'destination_capacity', 'can_fit', 'maximum_request_amount', 'blockers',
        ], array_keys($result));
        $this->assertSame(['id', 'name'], array_keys($result['item']));
    }
}
