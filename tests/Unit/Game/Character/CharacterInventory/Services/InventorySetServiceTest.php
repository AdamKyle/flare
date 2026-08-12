<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\InventorySet;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class InventorySetServiceTest extends TestCase
{
    use CreateInventorySets, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?InventorySetService $inventorySetService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->inventorySetService = resolve(InventorySetService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->inventorySetService = null;
    }

    public function test_assign_item_to_set_does_nothing_for_the_batch_crafting_set(): void
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
        ]);

        $this->inventorySetService->assignItemToSet($set, $slot);

        $this->assertSame(0, $set->slots()->count());
        $this->assertNotNull($slot->fresh());
    }

    public function test_fetch_set_equippability_details_returns_error_for_a_set_the_character_does_not_own(): void
    {
        $character = $this->character->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $set = $this->createInventorySet(['character_id' => $otherCharacter->id]);

        $result = $this->inventorySetService->fetchSetEquippablityDetails($character, $set);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Not allowed to access a set you do not own.', $result['message']);
    }

    public function test_fetch_set_equippability_details_returns_grouped_type_counts(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring'])->id]);

        $result = $this->inventorySetService->fetchSetEquippablityDetails($character, $set);

        $this->assertSame(200, $result['status']);
        $this->assertSame(['type' => 'ring', 'count' => 2], $result[0]);
    }

    public function test_put_item_into_set_does_nothing_for_the_batch_crafting_set(): void
    {
        $item = $this->createItem();
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
        ]);

        $result = $this->inventorySetService->putItemIntoSet($set, $item);

        $this->assertNull($result);
        $this->assertSame(0, $set->slots()->count());
    }

    public function test_put_item_into_set_creates_a_set_slot(): void
    {
        $item = $this->createItem();
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $result = $this->inventorySetService->putItemIntoSet($set, $item);

        $this->assertNotNull($result);
        $this->assertSame(1, $set->slots()->count());
    }

    public function test_move_item_to_set_returns_error_when_slot_or_set_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->inventorySetService->moveItemToSet($character, 999999, 999999);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Either the slot or the inventory set does not exist.', $result['message']);
    }

    public function test_move_item_to_set_returns_error_for_the_batch_crafting_set(): void
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
        ]);

        $result = $this->inventorySetService->moveItemToSet($character, $slot->id, $set->id);

        $this->assertSame(422, $result['status']);
        $this->assertSame('You cannot manually move items into the Batch Crafting set.', $result['message']);
    }

    public function test_move_item_to_set_with_fire_events_and_named_set_returns_named_message(): void
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $set = $this->createInventorySet(['character_id' => $character->id, 'name' => 'My Named Set']);

        $result = $this->inventorySetService->moveItemToSet($character, $slot->id, $set->id, true);

        $this->assertSame(200, $result['status']);
        $this->assertStringContainsString('My Named Set', $result['message']);
    }

    public function test_move_item_to_set_with_fire_events_and_unnamed_set_returns_indexed_message(): void
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $result = $this->inventorySetService->moveItemToSet($character, $slot->id, $set->id, true);

        $this->assertSame(200, $result['status']);
        $this->assertStringContainsString('Set ', $result['message']);
    }

    public function test_move_item_to_set_without_fire_events_or_is_last_returns_null(): void
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $result = $this->inventorySetService->moveItemToSet($character, $slot->id, $set->id, false, false);

        $this->assertNull($result);
    }

    public function test_move_item_to_set_with_is_last_and_named_set_returns_moved_to_set_name(): void
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $set = $this->createInventorySet(['character_id' => $character->id, 'name' => 'Stash']);

        $result = $this->inventorySetService->moveItemToSet($character, $slot->id, $set->id, false, true);

        $this->assertSame(200, $result['status']);
        $this->assertSame('Stash', $result['moved_to_set_name']);
    }

    public function test_move_item_to_set_with_is_last_and_unnamed_set_returns_indexed_moved_to_set_name(): void
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $result = $this->inventorySetService->moveItemToSet($character, $slot->id, $set->id, false, true);

        $this->assertSame(200, $result['status']);
        $this->assertStringContainsString('Set ', $result['moved_to_set_name']);
    }

    public function test_remove_item_from_inventory_set_returns_error_when_set_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->inventorySetService->removeItemFromInventorySet($character, 999999, 1);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Not allowed to do that.', $result['message']);
    }

    public function test_remove_item_from_inventory_set_returns_error_when_set_is_equipped(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => true]);

        $result = $this->inventorySetService->removeItemFromInventorySet($character, $set->id, 1);

        $this->assertSame(422, $result['status']);
        $this->assertSame('You cannot move an equipped item into your inventory from this set. Unequip the set first.', $result['message']);
    }

    public function test_remove_item_from_inventory_set_returns_error_when_slot_not_in_set(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $result = $this->inventorySetService->removeItemFromInventorySet($character, $set->id, 999999);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Item does not exist in this set.', $result['message']);
    }

    public function test_remove_item_from_inventory_set_returns_error_when_inventory_is_full(): void
    {
        $item = $this->createItem();
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $character->update(['inventory_max' => 0]);

        $result = $this->inventorySetService->removeItemFromInventorySet($character, $set->id, $setSlot->id);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Not enough inventory space to put this item back into your inventory.', $result['message']);
    }

    public function test_remove_item_from_inventory_set_succeeds_for_a_named_set(): void
    {
        $item = $this->createItem();
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id, 'name' => 'Stash']);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $result = $this->inventorySetService->removeItemFromInventorySet($character, $set->id, $setSlot->id);

        $this->assertSame(200, $result['status']);
        $this->assertStringContainsString('Stash', $result['message']);
    }

    public function test_remove_item_from_inventory_set_succeeds_for_an_unnamed_set(): void
    {
        $item = $this->createItem();
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $result = $this->inventorySetService->removeItemFromInventorySet($character, $set->id, $setSlot->id);

        $this->assertSame(200, $result['status']);
        $this->assertStringContainsString('Set ', $result['message']);
    }

    public function test_empty_set_returns_error_when_inventory_is_full(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $character->update(['inventory_max' => 0]);

        $result = $this->inventorySetService->emptySet($character, $set);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Your inventory is full. Cannot remove items from set.', $result['message']);
    }

    public function test_empty_set_returns_error_when_character_does_not_own_the_set(): void
    {
        $character = $this->character->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $set = $this->createInventorySet(['character_id' => $otherCharacter->id]);

        $result = $this->inventorySetService->emptySet($character, $set);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Cannot do that.', $result['message']);
    }

    public function test_empty_set_returns_error_when_not_enough_room(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem()->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem()->id]);
        $character->update(['inventory_max' => 1]);

        $result = $this->inventorySetService->emptySet($character, $set);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Your inventory does not have enough room to empty this set.', $result['message']);
    }

    public function test_empty_set_succeeds_for_a_named_set(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id, 'name' => 'Stash']);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem()->id]);

        $result = $this->inventorySetService->emptySet($character, $set);

        $this->assertSame(200, $result['status']);
        $this->assertStringContainsString('Stash', $result['message']);
    }

    public function test_empty_set_succeeds_for_an_unnamed_set(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem()->id]);

        $result = $this->inventorySetService->emptySet($character, $set);

        $this->assertSame(200, $result['status']);
        $this->assertStringContainsString('Set ', $result['message']);
    }

    public function test_equip_inventory_set_unequips_a_previously_equipped_set(): void
    {
        $character = $this->character->getCharacter();
        $oldSet = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => true]);
        $this->createInventorySetSlot(['inventory_set_id' => $oldSet->id, 'item_id' => $this->createItem(['type' => 'body'])->id, 'equipped' => true]);
        $newSet = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $newSet->id, 'item_id' => $this->createItem(['type' => 'body', 'default_position' => 'body'])->id]);

        $this->inventorySetService->equipInventorySet($character, $newSet);

        $this->assertFalse($oldSet->fresh()->is_equipped);
        $this->assertTrue($newSet->fresh()->is_equipped);
    }

    public function test_equip_inventory_set_equips_every_supported_item_type(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $weapon = $this->createItem(['type' => 'sword']);
        $shield = $this->createItem(['type' => 'shield']);
        $bow = $this->createItem(['type' => 'bow']);
        $ring = $this->createItem(['type' => 'ring']);
        $spell = $this->createItem(['type' => 'spell-damage']);
        $trinket = $this->createItem(['type' => 'trinket']);
        $armour = $this->createItem(['type' => 'body', 'default_position' => 'body']);

        $this->createInventorySetSlotsForItems($set, [
            $weapon->id, $shield->id, $bow->id, $ring->id, $spell->id, $trinket->id, $armour->id,
        ]);

        $this->inventorySetService->equipInventorySet($character, $set);

        $this->assertTrue($set->fresh()->is_equipped);
        $this->assertSame(7, $set->fresh()->slots()->where('equipped', true)->count());
    }

    public function test_is_set_equippable_returns_true_for_an_empty_set(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $this->assertTrue($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_false_when_hand_positions_are_invalid(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'bow'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'sword'])->id]);

        $this->assertFalse($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_false_when_duplicate_armour_type_present(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'body'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'body'])->id]);

        $this->assertFalse($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_false_when_more_than_one_trinket(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'trinket'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'trinket'])->id]);

        $this->assertFalse($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_false_when_more_than_two_rings(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring'])->id]);

        $this->assertFalse($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_false_when_two_healing_spells_and_a_damage_spell(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'spell-healing'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'spell-healing'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'spell-damage'])->id]);

        $this->assertFalse($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_false_when_more_than_two_damage_spells(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'spell-damage'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'spell-damage'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'spell-damage'])->id]);

        $this->assertFalse($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_false_when_more_than_two_healing_spells(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'spell-healing'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'spell-healing'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'spell-healing'])->id]);

        $this->assertFalse($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_false_when_one_healing_and_two_damage_spells(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'spell-healing'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'spell-damage'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'spell-damage'])->id]);

        $this->assertFalse($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_true_for_a_single_cosmic_item(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring', 'is_cosmic' => true])->id]);

        $this->assertTrue($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_true_for_a_single_unique_item_via_suffix(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $suffix = $this->createItemAffix(['randomly_generated' => true]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring', 'item_suffix_id' => $suffix->id])->id]);

        $this->assertTrue($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_false_when_more_than_one_artifact(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'artifact'])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'artifact'])->id]);

        $this->assertFalse($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_false_when_more_than_one_unique_item(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['randomly_generated' => true]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring', 'item_prefix_id' => $prefix->id])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring', 'item_prefix_id' => $prefix->id])->id]);

        $this->assertFalse($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_false_when_more_than_one_mythic_item(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring', 'is_mythic' => true])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring', 'is_mythic' => true])->id]);

        $this->assertFalse($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_false_when_more_than_one_cosmic_item(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring', 'is_cosmic' => true])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring', 'is_cosmic' => true])->id]);

        $this->assertFalse($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_false_when_unique_and_mythic_mixed(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $prefix = $this->createItemAffix(['randomly_generated' => true]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring', 'item_prefix_id' => $prefix->id])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'trinket', 'is_mythic' => true])->id]);

        $this->assertFalse($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_is_set_equippable_returns_false_when_mythic_and_cosmic_mixed(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'ring', 'is_mythic' => true])->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem(['type' => 'trinket', 'is_cosmic' => true])->id]);

        $this->assertFalse($this->inventorySetService->isSetEquippable($set->fresh()));
    }

    public function test_unequip_set_succeeds_for_a_named_set(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => true, 'name' => 'Stash']);

        $result = $this->inventorySetService->unequipSet($character);

        $this->assertSame(200, $result['status']);
        $this->assertStringContainsString('Stash', $result['message']);
        $this->assertFalse($set->fresh()->is_equipped);
    }

    public function test_equip_set_returns_error_for_batch_crafting_set(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
        ]);

        $result = $this->inventorySetService->equipSet($character, $set);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Batch Crafting set cannot be equipped.', $result['message']);
    }

    public function test_equip_set_returns_error_when_set_cannot_be_equipped(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id, 'can_be_equipped' => false]);

        $result = $this->inventorySetService->equipSet($character, $set);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Set cannot be equipped. It violates the set rules.', $result['message']);
    }

    public function test_equip_set_returns_error_when_character_does_not_own_the_set(): void
    {
        $character = $this->character->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $set = $this->createInventorySet(['character_id' => $otherCharacter->id]);

        $result = $this->inventorySetService->equipSet($character, $set);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Cannot do that.', $result['message']);
    }

    public function test_equip_set_succeeds_for_a_named_set(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id, 'name' => 'Stash']);

        $result = $this->inventorySetService->equipSet($character, $set);

        $this->assertSame(200, $result['status']);
        $this->assertStringContainsString('Stash', $result['message']);
    }

    public function test_rename_inventory_set_returns_error_when_set_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->inventorySetService->renameInventorySet($character, 999999, 'New Name');

        $this->assertSame(422, $result['status']);
        $this->assertSame('Set does not exist.', $result['message']);
    }

    public function test_rename_inventory_set_returns_error_for_batch_crafting_set(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
        ]);

        $result = $this->inventorySetService->renameInventorySet($character, $set->id, 'New Name');

        $this->assertSame(422, $result['status']);
        $this->assertSame('Batch Crafting set cannot be renamed.', $result['message']);
    }

    public function test_rename_inventory_set_returns_error_when_name_already_taken(): void
    {
        $character = $this->character->getCharacter();
        $this->createInventorySet(['character_id' => $character->id, 'name' => 'Taken Name']);
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $result = $this->inventorySetService->renameInventorySet($character->fresh(), $set->id, 'Taken Name');

        $this->assertSame(422, $result['status']);
        $this->assertSame('You already have a set with this name. Pick something else.', $result['message']);
    }

    public function test_rename_inventory_set_updates_the_name(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $result = $this->inventorySetService->renameInventorySet($character, $set->id, 'Brand New Name');

        $this->assertSame(200, $result['status']);
        $this->assertSame('Renamed set to: Brand New Name', $result['message']);
        $this->assertSame('Brand New Name', $set->fresh()->name);
    }

    public function test_save_equipped_items_to_set_returns_error_when_set_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->inventorySetService->saveEquippedItemsToSet($character, 999999);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Set does not exist.', $result['message']);
    }

    public function test_save_equipped_items_to_set_returns_error_for_batch_crafting_set(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
        ]);

        $result = $this->inventorySetService->saveEquippedItemsToSet($character, $set->id);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Cannot save equipped items to the Batch Crafting set.', $result['message']);
    }

    public function test_save_equipped_items_to_set_returns_error_when_set_is_not_empty(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $this->createItem()->id]);

        $result = $this->inventorySetService->saveEquippedItemsToSet($character, $set->id);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Set must be empty.', $result['message']);
    }

    public function test_save_equipped_items_to_set_succeeds_for_a_named_set(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()->giveItem($item, true, 'body')->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id, 'name' => 'Stash']);

        $result = $this->inventorySetService->saveEquippedItemsToSet($character, $set->id);

        $this->assertSame(200, $result['status']);
        $this->assertStringContainsString('Stash', $result['message']);
    }
}
