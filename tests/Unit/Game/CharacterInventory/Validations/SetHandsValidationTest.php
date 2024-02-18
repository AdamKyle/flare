<?php

namespace Tests\Unit\Game\CharacterInventory\Validations;

use App\Flare\Values\ArmourTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\CharacterInventory\Validations\SetHandsValidation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class SetHandsValidationTest extends TestCase
{

    use RefreshDatabase, CreateItem, CreateItemAffix;

    private ?CharacterFactory $character;

    private ?SetHandsValidation $setHandsValidation;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $this->setHandsValidation = resolve(SetHandsValidation::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->setHandsValidation = null;
    }

    public function testValidationIsValidForSingleWeapon()
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

    public function testValidationIsValidForDuelHandedWeapon()
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

    public function testValidationIsValidForSingleWeaponAndShield() {
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

    public function testValidationIsValidForSingleWeaponAndMace() {
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

    public function testValidationIsValidForSingleWeaponAndFan() {
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

    public function testValidationIsValidForSingleWeaponAndGun() {
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

    public function testValidationIsValidForSingleWeaponAndScratchAwl() {
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

    public function testValidationIsValidForDuelWeapons() {
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

    public function testValidationFailsForMultipleWeapons() {
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

    public function testValidationFailsForSingleWeaponAndMultipleSecondaryWeapons() {

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

    public function testValidationFailsForMultipleWeaponsAndShield() {
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

    public function testValidationFailsForMultipleWeaponsAndGun() {
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

    public function testValidationFailsForMultipleWeaponsAndFan() {
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

    public function testValidationFailsForMultipleWeaponsAndMace() {
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

    public function testValidationFailsForMultipleWeaponsAndScratchAwl() {
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

    public function testValidationFailsForMultipleWeaponsAndStave() {
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

    public function testValidationFailsForMultipleWeaponsAndBow() {
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

    public function testValidationFailsForMultipleWeaponsAndHammer() {
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

    public function testValidationFailsForSingleWeaponAndStave() {
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

    public function testValidationFailsForSingleWeaponAndBow() {
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

    public function testValidationFailsForSingleWeaponsAndHammer() {
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

    public function testValidationFailsForSingleWeaponsAndMixed() {
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

    public function testValidationFailsForStaveAndWeapon() {
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

    public function testValidationFailsForStaveAndGun() {
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

    public function testValidationFailsForStaveAndMace() {
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

    public function testValidationFailsForStaveAndFan() {
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

    public function testValidationFailsForStaveAndScratchAwl() {
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

    public function testValidationFailsForStaveAndStave() {
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

    public function testValidationFailsForStaveAndBow() {
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

    public function testValidationFailsForStaveAndHammer() {
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

    public function testValidationFailsForStaveAndShield() {
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
