<?php

namespace Tests\Console\AfterDeployment;

use App\Console\AfterDeployment\CleanDuplicateQuestInventorySlots;
use App\Flare\Models\InventorySlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateInventorySlot;
use Tests\Traits\CreateItem;

class CleanDuplicateQuestInventorySlotsTest extends TestCase
{
    use CreateInventorySlot, CreateItem, RefreshDatabase;

    public function test_dry_run_does_not_delete_duplicate_quest_item_slots(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $questItem = $this->createItem(['type' => 'quest']);

        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);

        $this->assertEquals(0, $this->artisan('cleanup:duplicate-quest-inventory-slots'));

        $this->assertEquals(3, InventorySlot::where('inventory_id', $character->inventory->id)->where('item_id', $questItem->id)->count());
    }

    public function test_apply_keeps_lowest_slot_id(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $questItem = $this->createItem(['type' => 'quest']);

        $keeper = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);

        $this->assertEquals(0, $this->artisan('cleanup:duplicate-quest-inventory-slots', ['--apply' => true]));

        $remaining = InventorySlot::where('inventory_id', $character->inventory->id)->where('item_id', $questItem->id)->get();

        $this->assertCount(1, $remaining);
        $this->assertSame($keeper->id, $remaining->first()->id);
    }

    public function test_apply_deletes_only_later_duplicates_of_same_quest_item_in_same_inventory(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $questItem = $this->createItem(['type' => 'quest']);

        $keeper = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);
        $duplicateOne = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);
        $duplicateTwo = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);

        $this->assertEquals(0, $this->artisan('cleanup:duplicate-quest-inventory-slots', ['--apply' => true]));

        $this->assertNotNull(InventorySlot::find($keeper->id));
        $this->assertNull(InventorySlot::find($duplicateOne->id));
        $this->assertNull(InventorySlot::find($duplicateTwo->id));
    }

    public function test_apply_leaves_sole_quest_item_slot_untouched(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $questItem = $this->createItem(['type' => 'quest']);

        $soleSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);

        $this->assertEquals(0, $this->artisan('cleanup:duplicate-quest-inventory-slots', ['--apply' => true]));

        $this->assertNotNull(InventorySlot::find($soleSlot->id));
    }

    public function test_apply_leaves_another_unique_quest_item_in_same_inventory_untouched(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $duplicatedQuestItem = $this->createItem(['type' => 'quest']);
        $uniqueQuestItem = $this->createItem(['type' => 'quest']);

        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $duplicatedQuestItem->id]);
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $duplicatedQuestItem->id]);
        $uniqueSlot = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $uniqueQuestItem->id]);

        $this->assertEquals(0, $this->artisan('cleanup:duplicate-quest-inventory-slots', ['--apply' => true]));

        $this->assertNotNull(InventorySlot::find($uniqueSlot->id));
        $this->assertEquals(1, InventorySlot::where('inventory_id', $character->inventory->id)->where('item_id', $duplicatedQuestItem->id)->count());
    }

    public function test_apply_leaves_duplicate_non_quest_items_untouched(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $weapon = $this->createItem(['type' => 'weapon']);

        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $weapon->id]);
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $weapon->id]);

        $this->assertEquals(0, $this->artisan('cleanup:duplicate-quest-inventory-slots', ['--apply' => true]));

        $this->assertEquals(2, InventorySlot::where('inventory_id', $character->inventory->id)->where('item_id', $weapon->id)->count());
    }

    public function test_apply_leaves_quest_items_in_another_inventory_untouched(): void
    {
        $characterWithDuplicates = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $otherCharacter = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $questItem = $this->createItem(['type' => 'quest']);

        $this->createInventorySlot(['inventory_id' => $characterWithDuplicates->inventory->id, 'item_id' => $questItem->id]);
        $this->createInventorySlot(['inventory_id' => $characterWithDuplicates->inventory->id, 'item_id' => $questItem->id]);

        $otherSlot = $this->createInventorySlot(['inventory_id' => $otherCharacter->inventory->id, 'item_id' => $questItem->id]);

        $this->assertEquals(0, $this->artisan('cleanup:duplicate-quest-inventory-slots', ['--apply' => true]));

        $this->assertNotNull(InventorySlot::find($otherSlot->id));
        $this->assertEquals(1, InventorySlot::where('inventory_id', $characterWithDuplicates->inventory->id)->where('item_id', $questItem->id)->count());
    }

    public function test_second_apply_run_deletes_nothing(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $questItem = $this->createItem(['type' => 'quest']);

        $keeper = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);

        $this->assertEquals(0, $this->artisan('cleanup:duplicate-quest-inventory-slots', ['--apply' => true]));
        $this->assertEquals(0, $this->artisan('cleanup:duplicate-quest-inventory-slots', ['--apply' => true]));

        $remaining = InventorySlot::where('inventory_id', $character->inventory->id)->where('item_id', $questItem->id)->get();

        $this->assertCount(1, $remaining);
        $this->assertSame($keeper->id, $remaining->first()->id);
    }

    public function test_apply_verifies_exactly_one_slot_remains_with_the_original_lowest_id(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $questItem = $this->createItem(['type' => 'quest']);

        $keeper = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);
        $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $questItem->id]);

        $this->assertEquals(0, $this->artisan('cleanup:duplicate-quest-inventory-slots', ['--apply' => true]));

        $remaining = InventorySlot::where('inventory_id', $character->inventory->id)->where('item_id', $questItem->id)->get();

        $this->assertCount(1, $remaining);
        $this->assertSame($keeper->id, $remaining->first()->id);
    }

    public function test_command_is_registered_with_the_exact_signature(): void
    {
        $this->assertArrayHasKey('cleanup:duplicate-quest-inventory-slots', Artisan::all());
        $this->assertInstanceOf(CleanDuplicateQuestInventorySlots::class, Artisan::all()['cleanup:duplicate-quest-inventory-slots']);
    }
}
