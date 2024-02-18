<?php

namespace Tests\Unit\Game\CharacterInventory\Services;

use App\Flare\Values\ArmourTypes;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\CharacterInventory\Services\InventorySetService;
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

        $result = $this->inventorySetService->removeItemFromInventorySet($character->inventorySets()->first(), $itemToRemove);

        $this->assertEquals('Not enough inventory space to put this item back into your inventory.', $result['message']);
    }

    public function testCanRemoveItemFromSetBecauseInventoryIsNotMaxedOut() {
        $itemToRemove = $this->createItem();
        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($itemToRemove, 0)
            ->getCharacter();

        $result = $this->inventorySetService->removeItemFromInventorySet($character->inventorySets()->first(), $itemToRemove);

        $this->assertEquals('Removed ' . $itemToRemove->affix_name . ' from Set.', $result['message']);
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
}
