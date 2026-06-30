<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\InventorySet;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class MultiInventoryActionServiceTest extends TestCase
{
    use CreateItem, RefreshDatabase;

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
        $this->assertSame(295, $character->refresh()->gold);
    }
}
