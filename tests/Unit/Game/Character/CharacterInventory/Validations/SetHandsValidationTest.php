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

    public function test_no_hand_items_are_valid(): void
    {
        $this->assertTrue($this->setHandsValidation->areHandItemsValid(collect()));
    }

    public function test_one_current_single_handed_weapon_is_valid(): void
    {
        $this->assertTrue($this->setHandsValidation->areHandItemsValid(collect([$this->createItem(['type' => 'mace'])])));
    }

    public function test_one_shield_is_valid(): void
    {
        $this->assertTrue($this->setHandsValidation->areHandItemsValid(collect([$this->createItem(['type' => 'shield'])])));
    }

    public function test_one_bow_is_valid(): void
    {
        $this->assertTrue($this->setHandsValidation->areHandItemsValid(collect([$this->createItem(['type' => 'bow'])])));
    }

    public function test_one_stave_is_valid(): void
    {
        $this->assertTrue($this->setHandsValidation->areHandItemsValid(collect([$this->createItem(['type' => 'stave'])])));
    }

    public function test_one_hammer_is_valid(): void
    {
        $this->assertTrue($this->setHandsValidation->areHandItemsValid(collect([$this->createItem(['type' => 'hammer'])])));
    }

    public function test_two_current_single_handed_weapons_are_valid(): void
    {
        $this->assertTrue($this->setHandsValidation->areHandItemsValid(collect([
            $this->createItem(['type' => 'dagger']),
            $this->createItem(['type' => 'sword']),
        ])));
    }

    public function test_same_single_handed_weapon_type_twice_is_valid(): void
    {
        $this->assertTrue($this->setHandsValidation->areHandItemsValid(collect([
            $this->createItem(['type' => 'dagger']),
            $this->createItem(['type' => 'dagger']),
        ])));
    }

    public function test_single_handed_weapon_plus_shield_is_valid(): void
    {
        $this->assertTrue($this->setHandsValidation->areHandItemsValid(collect([
            $this->createItem(['type' => 'sword']),
            $this->createItem(['type' => 'shield']),
        ])));
    }

    public function test_shield_plus_single_handed_weapon_is_valid(): void
    {
        $this->assertTrue($this->setHandsValidation->areHandItemsValid(collect([
            $this->createItem(['type' => 'shield']),
            $this->createItem(['type' => 'wand']),
        ])));
    }

    public function test_shield_plus_shield_is_valid(): void
    {
        $this->assertTrue($this->setHandsValidation->areHandItemsValid(collect([
            $this->createItem(['type' => 'shield']),
            $this->createItem(['type' => 'shield']),
        ])));
    }

    public function test_bow_plus_any_second_hand_item_is_invalid(): void
    {
        $this->assertFalse($this->setHandsValidation->areHandItemsValid(collect([
            $this->createItem(['type' => 'bow']),
            $this->createItem(['type' => 'shield']),
        ])));
    }

    public function test_stave_plus_any_second_hand_item_is_invalid(): void
    {
        $this->assertFalse($this->setHandsValidation->areHandItemsValid(collect([
            $this->createItem(['type' => 'stave']),
            $this->createItem(['type' => 'dagger']),
        ])));
    }

    public function test_hammer_plus_any_second_hand_item_is_invalid(): void
    {
        $this->assertFalse($this->setHandsValidation->areHandItemsValid(collect([
            $this->createItem(['type' => 'hammer']),
            $this->createItem(['type' => 'shield']),
        ])));
    }

    public function test_two_two_handed_items_are_invalid(): void
    {
        $this->assertFalse($this->setHandsValidation->areHandItemsValid(collect([
            $this->createItem(['type' => 'bow']),
            $this->createItem(['type' => 'stave']),
        ])));
    }

    public function test_more_than_two_one_hand_items_are_invalid(): void
    {
        $this->assertFalse($this->setHandsValidation->areHandItemsValid(collect([
            $this->createItem(['type' => 'dagger']),
            $this->createItem(['type' => 'shield']),
            $this->createItem(['type' => 'sword']),
        ])));
    }

    public function test_dagger_is_recognized_as_single_handed(): void
    {
        $this->assertSame('single_handed', $this->setHandsValidation->handedness($this->createItem(['type' => 'dagger'])));
    }

    public function test_sword_is_recognized_as_single_handed(): void
    {
        $this->assertSame('single_handed', $this->setHandsValidation->handedness($this->createItem(['type' => 'sword'])));
    }

    public function test_claw_is_recognized_as_single_handed(): void
    {
        $this->assertSame('single_handed', $this->setHandsValidation->handedness($this->createItem(['type' => 'claw'])));
    }

    public function test_wand_is_recognized_as_single_handed(): void
    {
        $this->assertSame('single_handed', $this->setHandsValidation->handedness($this->createItem(['type' => 'wand'])));
    }

    public function test_censer_is_recognized_as_single_handed(): void
    {
        $this->assertSame('single_handed', $this->setHandsValidation->handedness($this->createItem(['type' => 'censer'])));
    }

    public function test_rings_and_spells_are_not_counted_as_hand_items(): void
    {
        $this->assertTrue($this->setHandsValidation->isInventorySetHandPositionsValid(
            $this->character
                ->inventoryManagement()
                ->getCharacterFactory()
                ->inventorySetManagement()
                ->createInventorySets(10)
                ->putItemInSet($this->createItem(['type' => 'ring']), 0)
                ->putItemInSet($this->createItem(['type' => 'spell-damage']), 0)
                ->getCharacter()
                ->inventorySets
                ->first()
        ));
    }

    public function test_armour_other_than_shield_is_not_counted_as_a_hand_item(): void
    {
        $this->assertTrue($this->setHandsValidation->isInventorySetHandPositionsValid(
            $this->character
                ->inventoryManagement()
                ->getCharacterFactory()
                ->inventorySetManagement()
                ->createInventorySets(10)
                ->putItemInSet($this->createItem(['type' => 'body']), 0)
                ->getCharacter()
                ->inventorySets
                ->first()
        ));
    }
}
