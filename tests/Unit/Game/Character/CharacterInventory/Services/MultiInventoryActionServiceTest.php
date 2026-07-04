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
}
