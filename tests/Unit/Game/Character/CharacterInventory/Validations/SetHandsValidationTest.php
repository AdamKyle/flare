<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Validations;

use App\Game\Character\CharacterInventory\Validations\SetHandsValidation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;

class SetHandsValidationTest extends TestCase
{
    use CreateInventorySets, CreateItem, RefreshDatabase;

    private ?SetHandsValidation $setHandsValidation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setHandsValidation = resolve(SetHandsValidation::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->setHandsValidation = null;
    }

    public function test_is_hand_item_returns_true_for_shield(): void
    {
        $item = $this->createItem(['type' => 'shield']);

        $this->assertTrue($this->setHandsValidation->isHandItem($item));
    }

    public function test_is_hand_item_returns_true_for_generic_weapon_type(): void
    {
        $item = $this->createItem(['type' => 'weapon']);

        $this->assertTrue($this->setHandsValidation->isHandItem($item));
    }

    public function test_is_hand_item_returns_true_for_a_valid_weapon_type(): void
    {
        $item = $this->createItem(['type' => 'sword']);

        $this->assertTrue($this->setHandsValidation->isHandItem($item));
    }

    public function test_is_hand_item_returns_false_for_non_weapon_type(): void
    {
        $item = $this->createItem(['type' => 'ring']);

        $this->assertFalse($this->setHandsValidation->isHandItem($item));
    }

    public function test_handedness_returns_shield_for_shield_type(): void
    {
        $item = $this->createItem(['type' => 'shield']);

        $this->assertSame('shield', $this->setHandsValidation->handedness($item));
    }

    public function test_handedness_returns_single_handed_for_generic_weapon_type(): void
    {
        $item = $this->createItem(['type' => 'weapon']);

        $this->assertSame('single_handed', $this->setHandsValidation->handedness($item));
    }

    public function test_handedness_returns_two_handed_for_bow(): void
    {
        $item = $this->createItem(['type' => 'bow']);

        $this->assertSame('two_handed', $this->setHandsValidation->handedness($item));
    }

    public function test_handedness_returns_single_handed_for_sword(): void
    {
        $item = $this->createItem(['type' => 'sword']);

        $this->assertSame('single_handed', $this->setHandsValidation->handedness($item));
    }

    public function test_handedness_returns_null_for_non_weapon_type(): void
    {
        $item = $this->createItem(['type' => 'ring']);

        $this->assertNull($this->setHandsValidation->handedness($item));
    }

    public function test_are_hand_items_valid_returns_true_for_empty_collection(): void
    {
        $this->assertTrue($this->setHandsValidation->areHandItemsValid(new Collection));
    }

    public function test_are_hand_items_valid_returns_false_when_more_than_two_items(): void
    {
        $items = new Collection([
            $this->createItem(['type' => 'sword']),
            $this->createItem(['type' => 'dagger']),
            $this->createItem(['type' => 'shield']),
        ]);

        $this->assertFalse($this->setHandsValidation->areHandItemsValid($items));
    }

    public function test_are_hand_items_valid_returns_false_when_a_non_hand_item_is_included(): void
    {
        $items = new Collection([
            $this->createItem(['type' => 'sword']),
            $this->createItem(['type' => 'ring']),
        ]);

        $this->assertFalse($this->setHandsValidation->areHandItemsValid($items));
    }

    public function test_are_hand_items_valid_returns_false_when_two_handed_weapon_is_paired_with_another_item(): void
    {
        $items = new Collection([
            $this->createItem(['type' => 'bow']),
            $this->createItem(['type' => 'shield']),
        ]);

        $this->assertFalse($this->setHandsValidation->areHandItemsValid($items));
    }

    public function test_are_hand_items_valid_returns_true_for_a_single_two_handed_weapon(): void
    {
        $items = new Collection([
            $this->createItem(['type' => 'bow']),
        ]);

        $this->assertTrue($this->setHandsValidation->areHandItemsValid($items));
    }

    public function test_are_hand_items_valid_returns_true_for_two_single_handed_weapons(): void
    {
        $items = new Collection([
            $this->createItem(['type' => 'sword']),
            $this->createItem(['type' => 'dagger']),
        ]);

        $this->assertTrue($this->setHandsValidation->areHandItemsValid($items));
    }

    public function test_is_inventory_set_hand_positions_valid_ignores_non_hand_items_in_the_set(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter(
            assignBaseSkill: false,
            assignPassiveSkills: false,
            createClassRanks: false,
        )->getCharacter();

        $sword = $this->createItem(['type' => 'sword']);
        $questItem = $this->createItem(['type' => 'quest']);

        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $sword->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $questItem->id]);

        $this->assertTrue($this->setHandsValidation->isInventorySetHandPositionsValid($set->refresh()));
    }

    public function test_is_inventory_set_hand_positions_valid_returns_false_for_invalid_hand_combination(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter(
            assignBaseSkill: false,
            assignPassiveSkills: false,
            createClassRanks: false,
        )->getCharacter();

        $bow = $this->createItem(['type' => 'bow']);
        $shield = $this->createItem(['type' => 'shield']);

        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $bow->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $shield->id]);

        $this->assertFalse($this->setHandsValidation->isInventorySetHandPositionsValid($set->refresh()));
    }
}
