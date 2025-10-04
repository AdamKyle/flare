<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Values\ArmourTypes;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class InventorySetServiceTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

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

    public function test_can_assign_item_to_set()
    {
        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem())
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10)
            ->getCharacter();

        $slot = $character->inventory->slots->first();

        $this->inventorySetService->assignItemToSet($character->inventorySets->first(), $slot);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
        $this->assertNotEmpty($character->inventorySets->first->slots);
    }

    public function test_put_item_into_set()
    {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(10)
            ->getCharacter();

        $item = $this->createItem();

        $this->inventorySetService->putItemIntoSet($character->inventorySets->first(), $item);

        $character = $character->refresh();

        $this->assertNotEmpty($character->inventorySets->first->slots);
    }

    public function test_cannot_remove_item_from_set_because_inventory_is_maxed_out()
    {
        $itemToRemove = $this->createItem();
        $character = $this->character
            ->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem(), 75)
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($itemToRemove, 0)
            ->getCharacter();

        $result = $this->inventorySetService->removeItemFromInventorySet($character, $character->inventorySets()->first()->id, $character->inventorySets()->first()->slots->first()->id);

        $this->assertEquals('Not enough inventory space to put this item back into your inventory.', $result['message']);
    }

    public function test_cannot_remove_item_from_set_because_inventory_set_is_not_yours()
    {
        $itemToRemove = $this->createItem();
        $character = $this->character
            ->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem(), 75)
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($itemToRemove, 0)
            ->getCharacter();

        $result = $this->inventorySetService->removeItemFromInventorySet($character, 977, $character->inventorySets()->first()->slots->first()->id);

        $this->assertEquals('Not allowed to do that.', $result['message']);
    }

    public function test_cannot_remove_item_from_set_because_inventory_set_is_equipped()
    {
        $itemToRemove = $this->createItem();
        $character = $this->character
            ->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem(), 75)
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($itemToRemove, 0)
            ->getCharacter();

        $character->inventorySets()->first()->update([
            'is_equipped' => true,
        ]);

        $character = $character->refresh();

        $result = $this->inventorySetService->removeItemFromInventorySet($character, $character->inventorySets()->first()->id, $character->inventorySets()->first()->slots->first()->id);

        $this->assertEquals('You cannot move an equipped item into your inventory from this set. Unequip the set first.', $result['message']);
    }

    public function test_cannot_remove_item_from_set_because_item_does_not_exist_in_inventory_set()
    {
        $itemToRemove = $this->createItem();
        $character = $this->character
            ->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem(), 75)
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($itemToRemove, 0)
            ->getCharacter();

        $character = $character->refresh();

        $result = $this->inventorySetService->removeItemFromInventorySet($character, $character->inventorySets()->first()->id, 9898);

        $this->assertEquals('Item does not exist in this set.', $result['message']);
    }

    public function test_can_remove_item_from_set_because_inventory_is_not_maxed_out()
    {
        $itemToRemove = $this->createItem();
        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10, true)
            ->putItemInSet($itemToRemove, 0)
            ->getCharacter();

        $result = $this->inventorySetService->removeItemFromInventorySet($character, $character->inventorySets()->first()->id, $character->inventorySets()->first()->slots->first()->id);

        $character = $character->refresh();

        $setName = $character->inventorySets->first()->name;

        $this->assertEquals('Removed '.$itemToRemove->affix_name.' from '.$setName.' and placed back into your inventory.', $result['message']);
    }

    public function test_can_remove_item_from_set_because_inventory_is_not_maxed_out_and_set_is_not_named()
    {
        $itemToRemove = $this->createItem();
        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($itemToRemove, 0)
            ->getCharacter();

        $result = $this->inventorySetService->removeItemFromInventorySet($character, $character->inventorySets()->first()->id, $character->inventorySets()->first()->slots->first()->id);

        $character = $character->refresh();

        $setName = 'Set 1';

        $this->assertEquals('Removed '.$itemToRemove->affix_name.' from '.$setName.' and placed back into your inventory.', $result['message']);
    }

    public function test_equip_full_set()
    {
        $itemTypes = [
            WeaponTypes::WEAPON,
            ArmourTypes::BODY,
            ArmourTypes::FEET,
            ArmourTypes::GLOVES,
            ArmourTypes::HELMET,
            ArmourTypes::LEGGINGS,
            ArmourTypes::SHIELD,
            ArmourTypes::SLEEVES,
            SpellTypes::DAMAGE,
            SpellTypes::HEALING,
            WeaponTypes::RING,
            WeaponTypes::RING,
            'trinket',
            'trinket',
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {

            if ($type === ArmourTypes::BODY) {
                $character = $character->putItemInSet($this->createItem(['type' => $type, 'default_position' => 'body']), 0);
            } else {
                $character = $character->putItemInSet($this->createItem(['type' => $type]), 0);
            }

        }

        $character = $character->getCharacter();

        $this->inventorySetService->equipInventorySet($character, $character->inventorySets->first());

        $character = $character->refresh();

        $this->assertTrue($character->inventorySets->first()->is_equipped);
    }

    public function test_equip_full_set_with_two_handed_weapon()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
            ArmourTypes::BODY,
            ArmourTypes::FEET,
            ArmourTypes::GLOVES,
            ArmourTypes::HELMET,
            ArmourTypes::LEGGINGS,
            ArmourTypes::SLEEVES,
            SpellTypes::DAMAGE,
            SpellTypes::HEALING,
            WeaponTypes::RING,
            WeaponTypes::RING,
            'trinket',
            'trinket',
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type]), 0);
        }

        $character = $character->getCharacter();

        $this->inventorySetService->equipInventorySet($character, $character->inventorySets->first());

        $character = $character->refresh();

        $this->assertTrue($character->inventorySets->first()->is_equipped);
    }

    public function test_equip_another_set_while_one_is_equipped()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
            ArmourTypes::BODY,
            ArmourTypes::FEET,
            ArmourTypes::GLOVES,
            ArmourTypes::HELMET,
            ArmourTypes::LEGGINGS,
            ArmourTypes::SLEEVES,
            SpellTypes::DAMAGE,
            SpellTypes::HEALING,
            WeaponTypes::RING,
            WeaponTypes::RING,
            'trinket',
            'trinket',
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type]), 0)->putItemInSet($this->createItem(['type' => $type]), 1);
        }

        $character = $character->getCharacter();

        $character->inventorySets()->first()->update([
            'is_equipped' => true,
        ]);

        $this->inventorySetService->equipInventorySet($character, $character->inventorySets[1]);

        $character = $character->refresh();

        $this->assertFalse($character->inventorySets->first()->is_equipped);
        $this->assertTrue($character->inventorySets[1]->is_equipped);
    }

    public function test_unequip_set()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
            ArmourTypes::BODY,
            ArmourTypes::FEET,
            ArmourTypes::GLOVES,
            ArmourTypes::HELMET,
            ArmourTypes::LEGGINGS,
            ArmourTypes::SLEEVES,
            SpellTypes::DAMAGE,
            SpellTypes::HEALING,
            WeaponTypes::RING,
            WeaponTypes::RING,
            'trinket',
            'trinket',
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type]), 0);
        }

        $character = $character->getCharacter();

        $character->inventorySets()->first()->update([
            'is_equipped' => true,
        ]);

        $character = $character->refresh();

        $this->inventorySetService->unEquipInventorySet($character->inventorySets->first());

        $character = $character->refresh();

        $this->assertFalse($character->inventorySets->first()->is_equipped);
    }

    public function test_set_is_not_equippable_for_weapons()
    {
        $itemTypes = [
            WeaponTypes::WEAPON,
            WeaponTypes::WEAPON,
            WeaponTypes::WEAPON,
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_set_is_not_equippable_for_armour()
    {
        $itemTypes = [
            ArmourTypes::BODY,
            ArmourTypes::BODY,
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_set_is_not_equippable_for_trinkets()
    {
        $itemTypes = [
            'trinket',
            'trinket',
            'trinket',
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_set_is_not_equippable_for_rings()
    {
        $itemTypes = [
            WeaponTypes::RING,
            WeaponTypes::RING,
            WeaponTypes::RING,
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_set_is_not_equippable_for_spells()
    {
        $itemTypes = [
            SpellTypes::DAMAGE,
            SpellTypes::DAMAGE,
            SpellTypes::HEALING,
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_set_is_not_equippable_for_spells_healing()
    {
        $itemTypes = [
            SpellTypes::HEALING,
            SpellTypes::HEALING,
            SpellTypes::HEALING,
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_set_is_not_equippable_for_spells_healing_and_damage()
    {
        $itemTypes = [
            SpellTypes::HEALING,
            SpellTypes::HEALING,
            SpellTypes::DAMAGE,
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_set_is_not_equippable_for_spells_damage()
    {
        $itemTypes = [
            SpellTypes::DAMAGE,
            SpellTypes::DAMAGE,
            SpellTypes::DAMAGE,
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_set_is_not_equippable_for_artifact()
    {
        $itemTypes = [
            'artifact',
            'artifact',
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_set_is_not_equippable_for_uniques_item_prefix()
    {
        $itemTypes = [
            WeaponTypes::WEAPON,
            WeaponTypes::RING,
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type, 'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
            ])]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_set_is_not_equippable_for_uniques_item_suffix()
    {
        $itemTypes = [
            WeaponTypes::WEAPON,
            WeaponTypes::RING,
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type, 'item_suffix_id' => $this->createItemAffix([
                'randomly_generated' => true,
            ])]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_set_is_not_equippable_for_mythics()
    {
        $itemTypes = [
            WeaponTypes::WEAPON,
            WeaponTypes::RING,
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type, 'is_mythic' => true, 'item_suffix_id' => $this->createItemAffix([
                'randomly_generated' => true,
            ])]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_set_is_not_equippable_for_cosmics()
    {
        $itemTypes = [
            WeaponTypes::WEAPON,
            WeaponTypes::RING,
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($itemTypes as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type, 'is_cosmic' => true, 'item_suffix_id' => $this->createItemAffix([
                'randomly_generated' => true,
            ])]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_set_is_not_equippable_for_uniques_and_mythics()
    {
        $mythics = [
            WeaponTypes::WEAPON,
        ];

        $uniques = [
            WeaponTypes::RING,
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($mythics as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type, 'is_mythic' => true, 'item_suffix_id' => $this->createItemAffix([
                'randomly_generated' => true,
            ])]), 0);
        }

        foreach ($uniques as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type, 'item_suffix_id' => $this->createItemAffix([
                'randomly_generated' => true,
            ])]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_set_is_not_equippable_for_cosmic_and_mythics()
    {
        $cosmics = [
            WeaponTypes::WEAPON,
        ];

        $mythics = [
            WeaponTypes::RING,
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($cosmics as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type, 'is_cosmic' => true, 'item_suffix_id' => $this->createItemAffix([
                'randomly_generated' => true,
            ])]), 0);
        }

        foreach ($mythics as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type, 'is_mythic' => true, 'item_suffix_id' => $this->createItemAffix([
                'randomly_generated' => true,
            ])]), 0);
        }

        $character = $character->getCharacter();

        $this->assertFalse($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_is_equippable_for_cosmic()
    {
        $cosmic = [
            WeaponTypes::WEAPON,
        ];

        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10);

        foreach ($cosmic as $type) {
            $character = $character->putItemInSet($this->createItem(['type' => $type, 'is_cosmic' => true, 'item_suffix_id' => $this->createItemAffix([
                'randomly_generated' => true,
            ])]), 0);
        }

        $character = $character->getCharacter();

        $this->assertTrue($this->inventorySetService->isSetEquippable($character->inventorySets->first()));
    }

    public function test_fail_to_move_item_to_set()
    {
        $character = $this->character->getCharacter();

        $result = $this->inventorySetService->moveItemToSet($character, 999, 9999);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Either the slot or the inventory set does not exist.', $result['message']);
    }

    public function test_move_to_set_that_does_not_have_a_name()
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacterFactory()->inventorySetManagement()->createInventorySets()->getCharacter();

        $result = $this->inventorySetService->moveItemToSet($character, $character->inventory->slots->first()->id, $character->inventorySets->first()->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals($item->affix_name.' Has been moved to: Set '. 1, $result['message']);
    }

    public function test_move_to_set_that_does_have_a_name()
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacterFactory()->inventorySetManagement()->createInventorySets(2, true)->getCharacter();

        $result = $this->inventorySetService->moveItemToSet($character, $character->inventory->slots->first()->id, $character->inventorySets->first()->id);

        $character = $character->refresh();

        $setName = $character->inventorySets->first()->name;

        $this->assertEquals(200, $result['status']);
        $this->assertEquals($item->affix_name.' Has been moved to: '.$setName, $result['message']);
    }

    public function test_cannot_rename_set_that_does_not_exist()
    {
        $character = $this->character->inventorySetManagement()->createInventorySets(2)->getCharacter();

        $result = $this->inventorySetService->renameInventorySet($character, 9999, 'jdfhjdfh');

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Set does not exist.', $result['message']);
    }

    public function test_cannot_rename_a_set_when_another_set_has_the_same_name()
    {
        $character = $this->character->inventorySetManagement()->createInventorySets()->getCharacter();

        $set = $character->inventorySets()->first();

        $set->update(['name' => 'sample']);

        $set = $set->refresh();
        $character = $character->refresh();

        $result = $this->inventorySetService->renameInventorySet($character, $set->id, $set->name);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You already have a set with this name. Pick something else.', $result['message']);
    }

    public function test_rename_the_set()
    {
        $character = $this->character->inventorySetManagement()->createInventorySets()->getCharacter();

        $set = $character->inventorySets()->first();

        $set->update(['name' => 'sample']);

        $set = $set->refresh();
        $character = $character->refresh();

        $result = $this->inventorySetService->renameInventorySet($character, $set->id, 'Apples');

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Renamed set to: Apples', $result['message']);
    }

    public function test_cannot_save_equipped_to_a_non_empty_set()
    {
        $character = $this->character->equipStartingEquipment()->inventorySetManagement()->createInventorySets(1, true)->putItemInSet($this->createItem(), 0)->getCharacter();

        $set = $character->inventorySets->first();

        $result = $this->inventorySetService->saveEquippedItemsToSet($character, $set->id);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Set must be empty.', $result['message']);
    }

    public function test_can_save_equipped_to_a_empty_set()
    {
        $character = $this->character->equipStartingEquipment()->inventorySetManagement()->createInventorySets(1, true)->getCharacter();

        $set = $character->inventorySets->first();

        $result = $this->inventorySetService->saveEquippedItemsToSet($character, $set->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals($set->refresh()->name.' is now equipped (equipment has been moved to the set).', $result['message']);
    }

    public function test_cannot_empty_set_when_inventory_is_maxed()
    {
        $character = $this->character
            ->equipStartingEquipment()
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $set = $character->inventorySets->first();

        $character->update([
            'inventory_max' => 0,
        ]);

        $character = $character->refresh();

        $result = $this->inventorySetService->emptySet($character, $set);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Your inventory is full. Cannot remove items from set.', $result['message']);
    }

    public function test_cannot_empty_inventory_set_you_do_not_own()
    {
        $character = $this->character
            ->equipStartingEquipment()
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $secondaryCharacter = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->equipStartingEquipment()
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $set = $secondaryCharacter->inventorySets->first();

        $result = $this->inventorySetService->emptySet($character, $set);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Cannot do that.', $result['message']);
    }

    public function test_can_move_some_items_from_the_set_to_inventory()
    {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $set = $character->inventorySets->first();

        $character->update([
            'inventory_max' => 1,
        ]);

        $character = $character->refresh();

        $result = $this->inventorySetService->emptySet($character, $set);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Removed '. 1 .' of '. 2 .' items from '.$set->name.'. If all items were not moved over, it is because your inventory became full.', $result['message']);
    }

    public function test_can_move_all_items_from_set_to_inventory()
    {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $set = $character->inventorySets->first();

        $character = $character->refresh();

        $result = $this->inventorySetService->emptySet($character, $set);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Removed '. 2 .' of '. 2 .' items from '.$set->name.'. If all items were not moved over, it is because your inventory became full.', $result['message']);
    }

    public function test_does_unequip_set()
    {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0, 'left-hand', true)
            ->putItemInSet($this->createItem(), 0, 'right-hand', true)
            ->getCharacter();

        $set = $character->inventorySets->first();

        $character = $character->refresh();

        $result = $this->inventorySetService->unequipSet($character, $set);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Unequipped '.$set->name.'.', $result['message']);

        $character = $character->refresh();

        $this->assertEmpty(
            $character->inventorySets()->where('is_equipped', true)->get()
        );
    }

    public function test_cannot_equip_set_when_set_is_not_allowed_to_be_equipped()
    {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $set = $character->inventorySets->first();

        $set->update([
            'can_be_equipped' => false,
        ]);

        $set = $set->refresh();

        $character = $character->refresh();

        $result = $this->inventorySetService->equipSet($character, $set);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Set cannot be equipped. It violates the set rules.', $result['message']);
    }

    public function test_cannot_equip_set_when_you_do_not_own_the_set()
    {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $secondCharacter = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $set = $secondCharacter->inventorySets->first();

        $character = $character->refresh();

        $result = $this->inventorySetService->equipSet($character, $set);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Cannot do that.', $result['message']);
    }

    public function test_can_equip_set()
    {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $set = $character->inventorySets->first();

        $character = $character->refresh();

        $result = $this->inventorySetService->equipSet($character, $set);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals($set->name.' is now equipped', $result['message']);

        $character = $character->refresh();

        $this->assertNotNull(
            $character->inventorySets()->where('is_equipped', true)->first()
        );
    }
}
