<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Setup;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\Setup\HolyOilsBatchCraftingSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;

class HolyOilsBatchCraftingSetupServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateInventorySets, CreateItem, RefreshDatabase;

    private ?HolyOilsBatchCraftingSetupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(HolyOilsBatchCraftingSetupService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_supports_only_holy_oils(): void
    {
        $this->assertTrue($this->service->supports(BatchCraftingType::HOLY_OILS));
        $this->assertFalse($this->service->supports(BatchCraftingType::ALCHEMY));
    }

    public function test_resolve_start_builds_selected_items_plan_from_owned_target_slots(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $character = $characterFactory->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();

        $result = $this->service->resolveStart($character->refresh(), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'target_slot_ids' => [$targetSlot->id], 'oil_slot_ids' => [1]],
        ]);

        $this->assertEmpty($result['blockers']);
        $this->assertCount(1, $result['progress']['plan']);
        $this->assertSame($targetSlot->id, $result['progress']['current_target_slot_id']);
        $this->assertSame(0, $result['progress']['plan_index']);
    }

    public function test_resolve_start_reports_a_blocker_when_no_target_slots_resolve(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->resolveStart($character, [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'target_slot_ids' => [999999], 'oil_slot_ids' => [1]],
        ]);

        $this->assertNotEmpty($result['blockers']);
    }

    public function test_resolve_start_builds_inventory_set_plan_from_set_contents(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $targetItem->id]);

        $result = $this->service->resolveStart($character, [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'inventory_set', 'inventory_set_id' => $set->id, 'oil_slot_ids' => [1]],
        ]);

        $this->assertEmpty($result['blockers']);
        $this->assertSame($set->id, $result['progress']['inventory_set_id']);
        $this->assertCount(1, $result['progress']['plan']);
        $this->assertSame($setSlot->id, $result['progress']['current_target_slot_id']);
    }

    public function test_resolve_start_reports_a_blocker_when_set_is_not_a_valid_target(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->resolveStart($character, [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'inventory_set', 'inventory_set_id' => 999999, 'oil_slot_ids' => [1]],
        ]);

        $this->assertNotEmpty($result['blockers']);
    }

    public function test_resolve_start_persists_the_listing_price_when_disposition_is_list(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $character = $characterFactory->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();

        $result = $this->service->resolveStart($character->refresh(), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'target_slot_ids' => [$targetSlot->id], 'oil_slot_ids' => [1], 'listing_price' => 75],
        ]);

        $this->assertSame(75, $result['progress']['listing_price']);
    }

    public function test_resolve_start_reports_a_blocker_when_the_character_has_no_inventory(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->inventory->delete();
        $character = $character->refresh();

        $result = $this->service->resolveStart($character, [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'target_slot_ids' => [1], 'oil_slot_ids' => [1]],
        ]);

        $this->assertNotEmpty($result['blockers']);
    }

    public function test_preview_builds_the_selected_items_preview_payload(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $character = $characterFactory->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $character->update(['gold_dust' => 5000]);
        $character = $character->refresh();
        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();
        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 5,
        ]);

        $result = $this->service->preview($character, [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'target_slot_ids' => [$targetSlot->id], 'oil_slot_ids' => [$oilSlot->id], 'listing_price' => null],
        ]);

        $this->assertSame(1, $result['target_count']);
    }

    public function test_preview_builds_the_inventory_set_preview_payload(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold_dust' => 5000]);
        $character = $character->refresh();
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $targetItem->id]);

        $result = $this->service->preview($character, [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'inventory_set', 'inventory_set_id' => $set->id, 'oil_slot_ids' => [], 'listing_price' => null],
        ]);

        $this->assertSame($set->id, $result['set_id']);
    }
}
