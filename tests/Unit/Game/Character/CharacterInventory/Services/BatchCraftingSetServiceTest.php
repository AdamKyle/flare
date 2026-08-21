<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;

class BatchCraftingSetServiceTest extends TestCase
{
    use CreateInventorySets, CreateItem, RefreshDatabase;

    private ?BatchCraftingSetService $batchCraftingSetService;

    private ?Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->batchCraftingSetService = resolve(BatchCraftingSetService::class);
        $this->character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->batchCraftingSetService = null;
        $this->character = null;
    }

    public function test_get_or_create_returns_same_set_on_subsequent_calls(): void
    {
        $character = $this->character;

        $first = $this->batchCraftingSetService->getOrCreateForCharacter($character);
        $second = $this->batchCraftingSetService->getOrCreateForCharacter($character);

        $this->assertSame($first->id, $second->id);
    }

    public function test_get_or_create_creates_set_with_batch_crafting_special_type(): void
    {
        $character = $this->character;

        $set = $this->batchCraftingSetService->getOrCreateForCharacter($character);

        $this->assertSame(InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, $set->special_type);
        $this->assertSame(InventorySet::BATCH_CRAFTING_MAX_SLOTS, $set->max_slots);
    }

    public function test_remaining_slots_declines_as_items_are_added(): void
    {
        $character = $this->character;
        $set = $this->batchCraftingSetService->getOrCreateForCharacter($character);
        $item = $this->createItem();
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $remaining = $this->batchCraftingSetService->remainingSlots($character);

        $this->assertSame(InventorySet::BATCH_CRAFTING_MAX_SLOTS - 1, $remaining);
    }

    public function test_can_accept_returns_true_when_space_exists(): void
    {
        $character = $this->character;

        $this->assertTrue($this->batchCraftingSetService->canAccept($character, 1));
    }

    public function test_can_accept_returns_false_when_set_is_full(): void
    {
        $character = $this->character;
        $set = $this->batchCraftingSetService->getOrCreateForCharacter($character);
        $item = $this->createItem();

        $this->fillInventorySetSlots($set, InventorySet::BATCH_CRAFTING_MAX_SLOTS, $item->id);

        $this->assertFalse($this->batchCraftingSetService->canAccept($character, 1));
    }

    public function test_create_item_in_batch_crafting_set_returns_success_true_on_success(): void
    {
        $character = $this->character;
        $item = $this->createItem();

        $result = $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $item);

        $this->assertTrue($result['success']);
        $this->assertNull($result['reason']);
        $this->assertNotNull($result['set_slot']);
        $this->assertSame($item->id, $result['set_slot']->item_id);
    }

    public function test_create_item_in_batch_crafting_set_never_creates_an_inventory_slot(): void
    {
        $character = $this->character;
        $item = $this->createItem();

        $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $item);

        $this->assertSame(0, $character->inventory->slots()->count());
    }

    public function test_create_item_in_batch_crafting_set_returns_set_full_when_no_remaining_slots(): void
    {
        $character = $this->character;
        $set = $this->batchCraftingSetService->getOrCreateForCharacter($character);
        $filler = $this->createItem();

        $this->fillInventorySetSlots($set, InventorySet::BATCH_CRAFTING_MAX_SLOTS, $filler->id);

        $result = $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $this->createItem());

        $this->assertFalse($result['success']);
        $this->assertSame('set_full', $result['reason']);
        $this->assertNull($result['set_slot']);
    }

    public function test_find_owned_slot_returns_null_and_does_not_create_a_set_when_none_exists(): void
    {
        $character = $this->character;

        $result = $this->batchCraftingSetService->findOwnedSlot($character, 1, 1);

        $this->assertNull($result);
        $this->assertSame(0, InventorySet::where('character_id', $character->id)->count());
    }

    public function test_replace_item_in_slot_swaps_the_item_without_changing_slot_count(): void
    {
        $character = $this->character;
        $item = $this->createItem();
        $placement = $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $item);
        $newItem = $this->createItem();
        $set = $this->batchCraftingSetService->getOrCreateForCharacter($character);

        $result = $this->batchCraftingSetService->replaceItemInSlot($character, $placement['set_slot']->id, $item->id, $newItem);

        $this->assertTrue($result['success']);
        $this->assertSame($newItem->id, $result['set_slot']->item_id);
        $this->assertSame($item->id, $result['displaced_item']->id);
        $this->assertSame(1, $set->slots()->count());
    }

    public function test_replace_item_in_slot_succeeds_even_when_the_set_is_at_full_capacity(): void
    {
        $character = $this->character;
        $item = $this->createItem();
        $set = $this->batchCraftingSetService->getOrCreateForCharacter($character);
        $placement = $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $item);
        $set->update(['max_slots' => 1]);
        $newItem = $this->createItem();

        $this->assertFalse($this->batchCraftingSetService->canAccept($character, 1));

        $result = $this->batchCraftingSetService->replaceItemInSlot($character, $placement['set_slot']->id, $item->id, $newItem);

        $this->assertTrue($result['success']);
        $this->assertSame($newItem->id, $result['set_slot']->item_id);
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_replace_item_in_slot_fails_when_the_slot_is_not_owned_by_the_character(): void
    {
        $character = $this->character;
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $item = $this->createItem();
        $placement = $this->batchCraftingSetService->createItemInBatchCraftingSet($otherCharacter, $item);
        $newItem = $this->createItem();

        $result = $this->batchCraftingSetService->replaceItemInSlot($character, $placement['set_slot']->id, $item->id, $newItem);

        $this->assertFalse($result['success']);
        $this->assertSame('slot_not_found', $result['reason']);
        $this->assertNull($result['set_slot']);
    }
}
