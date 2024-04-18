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

class InventorySetServiceTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateItemAffix;

    private ?CharacterFactory $character;

    private ?InventorySetService $inventorySetService;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $this->inventorySetService = resolve(InventorySetService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;

        $this->inventorySetService = null;
    }

    public function testCanAssignItemToSet() {
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

    public function testPutItemIntoSet() {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(10)
            ->getCharacter();

        $item = $this->createItem();

        $this->inventorySetService->putItemIntoSet($character->inventorySets->first(), $item);

        $character = $character->refresh();

        $this->assertNotEmpty($character->inventorySets->first->slots);
    }

    public function testCannotRemoveItemFromSetBecauseInventoryIsMaxedOut() {
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

    public function testCannotRemoveItemFromSetBecauseInventorySetIsNotYours() {
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

    public function testCannotRemoveItemFromSetBecauseInventorySetIsEquipped() {
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
            'is_equipped' => true
        ]);

        $character = $character->refresh();

        $result = $this->inventorySetService->removeItemFromInventorySet($character, $character->inventorySets()->first()->id, $character->inventorySets()->first()->slots->first()->id);

        $this->assertEquals('You cannot move an equipped item into your inventory from this set. Unequip the set first.', $result['message']);
    }

    public function testCannotRemoveItemFromSetBecauseItemDoesNotExistInInventorySet() {
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

    public function testCanRemoveItemFromSetBecauseInventoryIsNotMaxedOut() {
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

        $this->assertEquals('Removed ' . $itemToRemove->affix_name . ' from ' . $setName . ' and placed back into your inventory.', $result['message']);
    }

    public function testCanRemoveItemFromSetBecauseInventoryIsNotMaxedOutAndSetIsNotNamed() {
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

        $this->assertEquals('Removed ' . $itemToRemove->affix_name . ' from ' . $setName . ' and placed back into your inventory.', $result['message']);
    }

    public function testEquipFullSet() {
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
            'trinket'
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

    public function testEquipFullSetWithTwoHandedWeapon() {
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
            'trinket'
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

    public function testEquipAnotherSetWhileOneIsEquipped() {
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
            'trinket'
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

    public function testUnequipSet() {
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
            'trinket'
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

    public function testSetIsNotEquippableForWeapons() {
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

    public function testSetIsNotEquippableForArmour() {
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

    public function testSetIsNotEquippableForTrinkets() {
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

    public function testSetIsNotEquippableForRings() {
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

    public function testSetIsNotEquippableForSpells() {
        $itemTypes = [
            SpellTypes::DAMAGE,
            SpellTypes::DAMAGE,
            SpellTypes::HEALING
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

    public function testSetIsNotEquippableForSpellsHealing() {
        $itemTypes = [
            SpellTypes::HEALING,
            SpellTypes::HEALING,
            SpellTypes::HEALING
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

    public function testSetIsNotEquippableForSpellsHealingAndDamage() {
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

    public function testSetIsNotEquippableForSpellsDamage() {
        $itemTypes = [
            SpellTypes::DAMAGE,
            SpellTypes::DAMAGE,
            SpellTypes::DAMAGE
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

    public function testSetIsNotEquippableForArtifact() {
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

    public function testSetIsNotEquippableForUniquesItemPrefix() {
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

    public function testSetIsNotEquippableForUniquesItemSuffix() {
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

    public function testSetIsNotEquippableForMythics() {
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

    public function testSetIsNotEquippableForCosmics() {
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

    public function testSetIsNotEquippableForUniquesAndMythics() {
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

    public function testSetIsNotEquippableForCosmicAndMythics() {
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

    public function testIsEquippableForCosmic() {
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

    public function testFailToMoveItemToSet() {
        $character = $this->character->getCharacter();

        $result = $this->inventorySetService->moveItemToSet($character, 999, 9999);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Either the slot or the inventory set does not exist.', $result['message']);
    }

    public function testMoveToSetThatDoesNotHaveAName() {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacterFactory()->inventorySetManagement()->createInventorySets()->getCharacter();

        $result = $this->inventorySetService->moveItemToSet($character, $character->inventory->slots->first()->id, $character->inventorySets->first()->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals($item->affix_name . ' Has been moved to: Set ' . 1, $result['message']);
    }

    public function testMoveToSetThatDoesHaveAName() {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacterFactory()->inventorySetManagement()->createInventorySets(2, true)->getCharacter();

        $result = $this->inventorySetService->moveItemToSet($character, $character->inventory->slots->first()->id, $character->inventorySets->first()->id);

        $character = $character->refresh();

        $setName = $character->inventorySets->first()->name;

        $this->assertEquals(200, $result['status']);
        $this->assertEquals($item->affix_name . ' Has been moved to: ' . $setName, $result['message']);
    }

    public function testCannotRenameSetThatDoesNotExist() {
        $character = $this->character->inventorySetManagement()->createInventorySets(2)->getCharacter();

        $result = $this->inventorySetService->renameInventorySet($character, 9999, 'jdfhjdfh');

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Set does not exist.', $result['message']);
    }

    public function testCannotRenameASetWhenAnotherSetHasTheSameName() {
        $character = $this->character->inventorySetManagement()->createInventorySets()->getCharacter();

        $set = $character->inventorySets()->first();

        $set->update(['name' => 'sample']);

        $set = $set->refresh();
        $character = $character->refresh();

        $result = $this->inventorySetService->renameInventorySet($character, $set->id, $set->name);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You already have a set with this name. Pick something else.', $result['message']);
    }

    public function testRenameTheSet() {
        $character = $this->character->inventorySetManagement()->createInventorySets()->getCharacter();

        $set = $character->inventorySets()->first();

        $set->update(['name' => 'sample']);

        $set = $set->refresh();
        $character = $character->refresh();

        $result = $this->inventorySetService->renameInventorySet($character, $set->id, 'Apples');

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Renamed set to: Apples', $result['message']);
    }

    public function testCannotSaveEquippedToANonEmptySet() {
        $character = $this->character->equipStartingEquipment()->inventorySetManagement()->createInventorySets(1, true)->putItemInSet($this->createItem(), 0)->getCharacter();

        $set = $character->inventorySets->first();

        $result = $this->inventorySetService->saveEquippedItemsToSet($character, $set->id);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Set must be empty.', $result['message']);
    }

    public function testCanSaveEquippedToAEmptySet() {
        $character = $this->character->equipStartingEquipment()->inventorySetManagement()->createInventorySets(1, true)->getCharacter();

        $set = $character->inventorySets->first();

        $result = $this->inventorySetService->saveEquippedItemsToSet($character, $set->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals($set->refresh()->name . ' is now equipped (equipment has been moved to the set).', $result['message']);
    }

    public function testCannotEmptySetWhenInventoryIsMaxed() {
        $character = $this->character
            ->equipStartingEquipment()
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $set = $character->inventorySets->first();

        $character->update([
            'inventory_max' => 0
        ]);

        $character = $character->refresh();

        $result = $this->inventorySetService->emptySet($character, $set);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Your inventory is full. Cannot remove items from set.', $result['message']);
    }

    public function testCannotEmptyInventorySetYouDoNotOwn() {
        $character = $this->character
            ->equipStartingEquipment()
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $secondaryCharacter = (new CharacterFactory())
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

    public function testCanMoveSomeItemsFromTheSetToInventory() {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $set = $character->inventorySets->first();

        $character->update([
            'inventory_max' => 1
        ]);

        $character = $character->refresh();

        $result = $this->inventorySetService->emptySet($character, $set);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Removed ' . 1 . ' of ' . 2 . ' items from ' . $set->name . '. If all items were not moved over, it is because your inventory became full.', $result['message']);
    }

    public function testCanMoveAllItemsFromSetToInventory() {
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
        $this->assertEquals('Removed ' . 2 . ' of ' . 2 . ' items from ' . $set->name . '. If all items were not moved over, it is because your inventory became full.', $result['message']);
    }

    public function testDoesUnequipSet() {
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
        $this->assertEquals('Unequipped ' . $set->name .'.', $result['message']);

        $character = $character->refresh();

        $this->assertEmpty(
            $character->inventorySets()->where('is_equipped', true)->get()
        );
    }

    public function testCannotEquipSetWhenSetIsNotAllowedToBeEquipped() {
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

    public function testCannotEquipSetWhenYouDoNotOwnTheSet() {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $secondCharacter = (new CharacterFactory())
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

    public function testCanEquipSet() {
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
        $this->assertEquals($set->name .  ' is now equipped', $result['message']);

        $character = $character->refresh();

        $this->assertNotNull(
            $character->inventorySets()->where('is_equipped', true)->first()
        );
    }

}
