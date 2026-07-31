<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\InventorySet;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class BatchCraftingSetServiceTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?BatchCraftingSetService $batchCraftingSetService;

    public function setUp(): void
    {
        parent::setUp();

        $this->batchCraftingSetService = resolve(BatchCraftingSetService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->batchCraftingSetService = null;
    }

    public function testGetOrCreateReturnsSameSetOnSubsequentCalls(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $first = $this->batchCraftingSetService->getOrCreateForCharacter($character);
        $second = $this->batchCraftingSetService->getOrCreateForCharacter($character);

        $this->assertSame($first->id, $second->id);
    }

    public function testGetOrCreateCreatesSetWithBatchCraftingSpecialType(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $set = $this->batchCraftingSetService->getOrCreateForCharacter($character);

        $this->assertSame(InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, $set->special_type);
        $this->assertSame(InventorySet::BATCH_CRAFTING_MAX_SLOTS, $set->max_slots);
    }

    public function testRemainingSlotsDeclinesAsItemsAreAdded(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $this->batchCraftingSetService->getOrCreateForCharacter($character);
        $item = $this->createItem();
        $set->slots()->create(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $remaining = $this->batchCraftingSetService->remainingSlots($character);

        $this->assertSame(InventorySet::BATCH_CRAFTING_MAX_SLOTS - 1, $remaining);
    }

    public function testCanAcceptReturnsTrueWhenSpaceExists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->assertTrue($this->batchCraftingSetService->canAccept($character, 1));
    }

    public function testCanAcceptReturnsFalseWhenSetIsFull(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $this->batchCraftingSetService->getOrCreateForCharacter($character);
        $item = $this->createItem();

        for ($i = 0; $i < InventorySet::BATCH_CRAFTING_MAX_SLOTS; $i++) {
            $set->slots()->create(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        }

        $this->assertFalse($this->batchCraftingSetService->canAccept($character, 1));
    }

    public function testCreateItemInBatchCraftingSetReturnsSuccessTrueOnSuccess(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $item = $this->createItem();

        $result = $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $item);

        $this->assertTrue($result['success']);
        $this->assertNull($result['reason']);
        $this->assertNotNull($result['set_slot']);
        $this->assertSame($item->id, $result['set_slot']->item_id);
    }

    public function testCreateItemInBatchCraftingSetNeverCreatesAnInventorySlot(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $item = $this->createItem();

        $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $item);

        $this->assertSame(0, $character->inventory->slots()->count());
    }

    public function testCreateItemInBatchCraftingSetReturnsSetFullWhenNoRemainingSlots(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $this->batchCraftingSetService->getOrCreateForCharacter($character);
        $filler = $this->createItem();

        for ($i = 0; $i < InventorySet::BATCH_CRAFTING_MAX_SLOTS; $i++) {
            $set->slots()->create(['inventory_set_id' => $set->id, 'item_id' => $filler->id]);
        }

        $result = $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $this->createItem());

        $this->assertFalse($result['success']);
        $this->assertSame('set_full', $result['reason']);
        $this->assertNull($result['set_slot']);
    }
}
