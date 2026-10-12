<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\MarketBoard;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\CharacterInventoryServiceFactory;
use Tests\TestCase;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class MultiInventoryActionSetServiceTest extends TestCase
{
    use CreateInventorySets, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character;

    private ?MultiInventoryActionService $multiInventoryActionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->multiInventoryActionService = (new CharacterInventoryServiceFactory)->buildMultiInventoryActionService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();

        $this->character = null;
        $this->multiInventoryActionService = null;
    }

    public function test_bulk_set_slot_selling_rejects_a_set_not_owned_by_the_character(): void
    {
        $character = $this->character;
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $this->createInventorySet(['character_id' => $otherCharacter->id]);

        $result = $this->multiInventoryActionService->sellManySetSlots($character, $otherSet, []);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Cannot do that.', $result['message']);
    }

    public function test_bulk_set_slot_selling_updates_gold_and_deletes_selected_set_slots(): void
    {
        $firstItem = $this->createItem(['cost' => 100]);
        $secondItem = $this->createItem(['cost' => 200]);
        $character = $this->character;
        $character->update(['inventory_max' => 0]);
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $firstSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $firstItem->id]);
        $secondSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $secondItem->id]);

        $this->multiInventoryActionService->sellManySetSlots($character, $set, [$firstSlot->id, $secondSlot->id]);

        $this->assertSame(0, $set->refresh()->slots()->count());
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
        $this->assertSame(295, $character->refresh()->gold);
    }

    public function test_bulk_set_slot_disenchanting_deletes_selected_set_slots_and_dispatches_disenchant_many(): void
    {
        Bus::fake();

        $prefix = $this->createItemAffix(['name' => 'Bulk Disenchant Prefix', 'type' => 'prefix']);
        $item = $this->createItem(['cost' => 100, 'item_prefix_id' => $prefix->id]);
        $character = $this->character;
        $character->update(['inventory_max' => 0]);
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $slot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $this->multiInventoryActionService->disenchantManySetSlots($character, $set, [$slot->id]);

        $this->assertSame(0, $set->refresh()->slots()->count());
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
        Bus::assertDispatched(DisenchantMany::class);
    }

    public function test_bulk_set_slot_disenchanting_rejects_a_set_not_owned_by_the_character(): void
    {
        $character = $this->character;
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $this->createInventorySet(['character_id' => $otherCharacter->id]);

        $result = $this->multiInventoryActionService->disenchantManySetSlots($character, $otherSet, []);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Cannot do that.', $result['message']);
    }

    public function test_bulk_set_slot_destroying_rejects_a_set_not_owned_by_the_character(): void
    {
        $character = $this->character;
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $this->createInventorySet(['character_id' => $otherCharacter->id]);

        $result = $this->multiInventoryActionService->destroyManySetSlots($character, $otherSet, []);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Cannot do that.', $result['message']);
    }

    public function test_bulk_set_slot_destroying_deletes_only_selected_owned_set_slots(): void
    {
        $firstItem = $this->createItem(['type' => 'trinket']);
        $secondItem = $this->createItem();
        $thirdItem = $this->createItem();
        $character = $this->character;
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $otherSet = $this->createInventorySet([
            'character_id' => $otherCharacter->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $selectedTrinketSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $firstItem->id]);
        $selectedItemSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $secondItem->id]);
        $unselectedSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $thirdItem->id]);
        $otherSlot = $this->createInventorySetSlot(['inventory_set_id' => $otherSet->id, 'item_id' => $thirdItem->id]);

        $this->multiInventoryActionService->destroyManySetSlots($character, $set, [
            $selectedTrinketSlot->id,
            $selectedItemSlot->id,
            $otherSlot->id,
        ]);

        $this->assertFalse($set->slots()->where('id', $selectedTrinketSlot->id)->exists());
        $this->assertFalse($set->slots()->where('id', $selectedItemSlot->id)->exists());
        $this->assertTrue($set->slots()->where('id', $unselectedSlot->id)->exists());
        $this->assertTrue($otherSet->slots()->where('id', $otherSlot->id)->exists());
    }

    public function test_destroy_all_crafted_items_set_slots_rejects_normal_set_target(): void
    {
        $item = $this->createItem();
        $character = $this->character;
        $normalSet = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => 'Set 1',
            'is_equipped' => false,
            'can_be_equipped' => true,
        ]);
        $normalSetSlot = $this->createInventorySetSlot(['inventory_set_id' => $normalSet->id, 'item_id' => $item->id]);

        $result = $this->multiInventoryActionService->destroyAllCraftedItemsSetSlots($character, $normalSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($normalSet->slots()->where('id', $normalSetSlot->id)->exists());
    }

    public function test_sell_all_crafted_items_set_slots_rejects_normal_set_target(): void
    {
        $item = $this->createItem();
        $character = $this->character;
        $normalSet = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => 'Set 1',
            'is_equipped' => false,
            'can_be_equipped' => true,
        ]);
        $normalSetSlot = $this->createInventorySetSlot(['inventory_set_id' => $normalSet->id, 'item_id' => $item->id]);

        $result = $this->multiInventoryActionService->sellAllCraftedItemsSetSlots($character, $normalSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($normalSet->slots()->where('id', $normalSetSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_rejects_normal_set_target(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Normal Set Prefix', 'type' => 'prefix']);
        $item = $this->createItem(['item_prefix_id' => $prefix->id]);
        $character = $this->character;
        $normalSet = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => 'Set 1',
            'is_equipped' => false,
            'can_be_equipped' => true,
        ]);
        $normalSetSlot = $this->createInventorySetSlot(['inventory_set_id' => $normalSet->id, 'item_id' => $item->id]);

        $result = $this->multiInventoryActionService->disenchantAllCraftedItemsSetSlots($character, $normalSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($normalSet->slots()->where('id', $normalSetSlot->id)->exists());
    }

    public function test_list_many_set_slots_lists_eligible_items_on_the_market_board_and_removes_them_from_the_set(): void
    {
        $item = $this->createItem(['type' => 'weapon']);
        $character = $this->character;
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $slot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $result = $this->multiInventoryActionService->listManySetSlots($character, $set, [$slot->id], 500);

        $this->assertSame(200, $result['status']);
        $this->assertSame(1, MarketBoard::where('character_id', $character->id)->where('item_id', $item->id)->where('listed_price', 500)->count());
        $this->assertSame(0, $set->refresh()->slots()->count());
    }

    public function test_list_many_set_slots_rejects_a_set_not_owned_by_the_character(): void
    {
        $character = $this->character;
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $this->createInventorySet(['character_id' => $otherCharacter->id]);

        $result = $this->multiInventoryActionService->listManySetSlots($character, $otherSet, [], 100);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Cannot do that.', $result['message']);
    }
}
