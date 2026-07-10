<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\InventorySet;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class MultiInventoryActionServiceTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

    public function testBulkInventorySellingUpdatesGoldAndDeletesSelectedSlots(): void
    {
        $firstItem = $this->createItem(['cost' => 100]);
        $secondItem = $this->createItem(['cost' => 200]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($firstItem)
            ->giveItem($secondItem)
            ->getCharacter();
        $slotIds = $character->inventory->slots->pluck('id')->all();

        resolve(MultiInventoryActionService::class)->sellManyItems($character, $slotIds);

        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
        $this->assertSame(295, $character->refresh()->gold);
    }

    public function testBulkSetSlotSellingUpdatesGoldAndDeletesSelectedSetSlots(): void
    {
        $firstItem = $this->createItem(['cost' => 100]);
        $secondItem = $this->createItem(['cost' => 200]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['inventory_max' => 0]);
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $firstSlot = $set->slots()->create(['item_id' => $firstItem->id]);
        $secondSlot = $set->slots()->create(['item_id' => $secondItem->id]);

        resolve(MultiInventoryActionService::class)->sellManySetSlots($character, $set, [$firstSlot->id, $secondSlot->id]);

        $this->assertSame(0, $set->refresh()->slots()->count());
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
        $this->assertSame(295, $character->refresh()->gold);
    }

    public function testBulkSetSlotDisenchantingDeletesSelectedSetSlotsAndDispatchesDisenchantMany(): void
    {
        Bus::fake();

        $prefix = $this->createItemAffix(['name' => 'Bulk Disenchant Prefix', 'type' => 'prefix']);
        $item = $this->createItem(['cost' => 100, 'item_prefix_id' => $prefix->id]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['inventory_max' => 0]);
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $slot = $set->slots()->create(['item_id' => $item->id]);

        resolve(MultiInventoryActionService::class)->disenchantManySetSlots($character, $set, [$slot->id]);

        $this->assertSame(0, $set->refresh()->slots()->count());
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
        Bus::assertDispatched(DisenchantMany::class);
    }

    public function testBulkSetSlotDestroyingDeletesOnlySelectedOwnedSetSlots(): void
    {
        $firstItem = $this->createItem(['type' => 'trinket']);
        $secondItem = $this->createItem();
        $thirdItem = $this->createItem();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $otherSet = $otherCharacter->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $selectedTrinketSlot = $set->slots()->create(['item_id' => $firstItem->id]);
        $selectedItemSlot = $set->slots()->create(['item_id' => $secondItem->id]);
        $unselectedSlot = $set->slots()->create(['item_id' => $thirdItem->id]);
        $otherSlot = $otherSet->slots()->create(['item_id' => $thirdItem->id]);

        resolve(MultiInventoryActionService::class)->destroyManySetSlots($character, $set, [
            $selectedTrinketSlot->id,
            $selectedItemSlot->id,
            $otherSlot->id,
        ]);

        $this->assertFalse($set->slots()->where('id', $selectedTrinketSlot->id)->exists());
        $this->assertFalse($set->slots()->where('id', $selectedItemSlot->id)->exists());
        $this->assertTrue($set->slots()->where('id', $unselectedSlot->id)->exists());
        $this->assertTrue($otherSet->slots()->where('id', $otherSlot->id)->exists());
    }

    public function testDestroyAllCraftedItemsSetSlotsRemovesEveryTrinketAndNonTrinketSlot(): void
    {
        $trinket = $this->createItem(['type' => 'trinket']);
        $nonTrinket = $this->createItem();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $set->slots()->create(['item_id' => $trinket->id]);
        $set->slots()->create(['item_id' => $nonTrinket->id]);

        resolve(MultiInventoryActionService::class)->destroyAllCraftedItemsSetSlots($character, $set);

        $this->assertSame(0, $set->refresh()->slots()->count());
    }

    public function testDestroyAllCraftedItemsSetSlotsDoesNotRemoveNormalSetSlots(): void
    {
        $item = $this->createItem();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $craftedSet = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $normalSet = $character->inventorySets()->create([
            'name' => 'Set 1',
            'is_equipped' => false,
            'can_be_equipped' => true,
        ]);
        $normalSetSlot = $normalSet->slots()->create(['item_id' => $item->id]);

        resolve(MultiInventoryActionService::class)->destroyAllCraftedItemsSetSlots($character, $craftedSet);

        $this->assertTrue($normalSet->slots()->where('id', $normalSetSlot->id)->exists());
    }

    public function testDestroyAllCraftedItemsSetSlotsDoesNotRemoveAnotherCharactersSetSlots(): void
    {
        $item = $this->createItem();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $otherSet = $otherCharacter->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $otherSlot = $otherSet->slots()->create(['item_id' => $item->id]);

        $result = resolve(MultiInventoryActionService::class)->destroyAllCraftedItemsSetSlots($character, $otherSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($otherSet->slots()->where('id', $otherSlot->id)->exists());
    }

    public function testDestroyAllCraftedItemsSetSlotsRejectsNormalSetTarget(): void
    {
        $item = $this->createItem();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $normalSet = $character->inventorySets()->create([
            'name' => 'Set 1',
            'is_equipped' => false,
            'can_be_equipped' => true,
        ]);
        $normalSetSlot = $normalSet->slots()->create(['item_id' => $item->id]);

        $result = resolve(MultiInventoryActionService::class)->destroyAllCraftedItemsSetSlots($character, $normalSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($normalSet->slots()->where('id', $normalSetSlot->id)->exists());
    }
}
