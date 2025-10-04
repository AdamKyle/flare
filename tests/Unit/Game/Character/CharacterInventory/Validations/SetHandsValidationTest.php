<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Validations;

use App\Flare\Values\ArmourTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Character\CharacterInventory\Validations\SetHandsValidation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class SetHandsValidationTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?SetHandsValidation $setHandsValidation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->setHandsValidation = resolve(SetHandsValidation::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->setHandsValidation = null;
    }

    public function test_validation_is_valid_for_single_weapon()
    {
        $itemTypes = [
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

        $this->assertTrue($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_is_valid_for_duel_handed_weapon()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
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

        $this->assertTrue($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_is_valid_for_single_weapon_and_shield()
    {
        $itemTypes = [
            WeaponTypes::WEAPON,
            ArmourTypes::SHIELD,
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

        $this->assertTrue($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_is_valid_for_single_weapon_and_mace()
    {
        $itemTypes = [
            WeaponTypes::WEAPON,
            WeaponTypes::MACE,
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

        $this->assertTrue($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_is_valid_for_single_weapon_and_fan()
    {
        $itemTypes = [
            WeaponTypes::WEAPON,
            WeaponTypes::FAN,
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

        $this->assertTrue($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_is_valid_for_single_weapon_and_gun()
    {
        $itemTypes = [
            WeaponTypes::WEAPON,
            WeaponTypes::GUN,
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

        $this->assertTrue($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_is_valid_for_single_weapon_and_scratch_awl()
    {
        $itemTypes = [
            WeaponTypes::WEAPON,
            WeaponTypes::SCRATCH_AWL,
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

        $this->assertTrue($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_is_valid_for_duel_weapons()
    {
        $itemTypes = [
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

        $this->assertTrue($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_multiple_weapons()
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_single_weapon_and_multiple_secondary_weapons()
    {

        $itemTypes = [
            WeaponTypes::WEAPON,
            WeaponTypes::GUN,
            WeaponTypes::GUN,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_multiple_weapons_and_shield()
    {
        $itemTypes = [
            ArmourTypes::SHIELD,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_multiple_weapons_and_gun()
    {
        $itemTypes = [
            WeaponTypes::GUN,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_multiple_weapons_and_fan()
    {
        $itemTypes = [
            WeaponTypes::FAN,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_multiple_weapons_and_mace()
    {
        $itemTypes = [
            WeaponTypes::MACE,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_multiple_weapons_and_scratch_awl()
    {
        $itemTypes = [
            WeaponTypes::SCRATCH_AWL,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_multiple_weapons_and_stave()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_multiple_weapons_and_bow()
    {
        $itemTypes = [
            WeaponTypes::BOW,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_multiple_weapons_and_hammer()
    {
        $itemTypes = [
            WeaponTypes::HAMMER,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_single_weapon_and_stave()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_single_weapon_and_bow()
    {
        $itemTypes = [
            WeaponTypes::BOW,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_single_weapons_and_hammer()
    {
        $itemTypes = [
            WeaponTypes::HAMMER,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_single_weapons_and_mixed()
    {
        $itemTypes = [
            WeaponTypes::FAN,
            WeaponTypes::WEAPON,
            WeaponTypes::GUN,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_stave_and_weapon()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_stave_and_gun()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
            WeaponTypes::GUN,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_stave_and_mace()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
            WeaponTypes::MACE,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_stave_and_fan()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
            WeaponTypes::FAN,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_stave_and_scratch_awl()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
            WeaponTypes::SCRATCH_AWL,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_stave_and_stave()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
            WeaponTypes::STAVE,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_stave_and_bow()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
            WeaponTypes::BOW,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_stave_and_hammer()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
            WeaponTypes::HAMMER,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }

    public function test_validation_fails_for_stave_and_shield()
    {
        $itemTypes = [
            WeaponTypes::STAVE,
            ArmourTypes::SHIELD,
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

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($character->inventorySets->first()));
    }
}
