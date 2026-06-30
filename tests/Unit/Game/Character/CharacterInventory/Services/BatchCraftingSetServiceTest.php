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

    public function testMoveInventorySlotReturnsSuccessTrueOnSuccess(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($this->createItem())
            ->getCharacterFactory()
            ->getCharacter();

        $slot = $character->inventory->slots->first();
        $result = $this->batchCraftingSetService->moveInventorySlotIntoBatchCraftingSet($character, $slot);

        $this->assertTrue($result['success']);
        $this->assertNull($result['reason']);
        $this->assertNotNull($result['set_slot']);
    }

    public function testMoveInventorySlotDeletesInventorySlotOnSuccess(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($this->createItem())
            ->getCharacterFactory()
            ->getCharacter();

        $slot = $character->inventory->slots->first();
        $slotId = $slot->id;
        $this->batchCraftingSetService->moveInventorySlotIntoBatchCraftingSet($character, $slot);

        $this->assertNull($character->refresh()->inventory->slots()->find($slotId));
    }

    public function testMoveInventorySlotReturnsNotOwnedWhenSlotBelongsToOtherCharacter(): void
    {
        $ownerCharacter = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($this->createItem())
            ->getCharacterFactory()
            ->getCharacter();

        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $slot = $ownerCharacter->inventory->slots->first();

        $result = $this->batchCraftingSetService->moveInventorySlotIntoBatchCraftingSet($otherCharacter, $slot);

        $this->assertFalse($result['success']);
        $this->assertSame('not_owned', $result['reason']);
        $this->assertNull($result['set_slot']);
    }

    public function testMoveInventorySlotReturnsSetFullWhenNoRemainingSlots(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($this->createItem())
            ->getCharacterFactory()
            ->getCharacter();

        $set = $this->batchCraftingSetService->getOrCreateForCharacter($character);
        $filler = $this->createItem();

        for ($i = 0; $i < InventorySet::BATCH_CRAFTING_MAX_SLOTS; $i++) {
            $set->slots()->create(['inventory_set_id' => $set->id, 'item_id' => $filler->id]);
        }

        $slot = $character->inventory->slots->first();
        $result = $this->batchCraftingSetService->moveInventorySlotIntoBatchCraftingSet($character, $slot);

        $this->assertFalse($result['success']);
        $this->assertSame('set_full', $result['reason']);
        $this->assertNull($result['set_slot']);
    }
}
