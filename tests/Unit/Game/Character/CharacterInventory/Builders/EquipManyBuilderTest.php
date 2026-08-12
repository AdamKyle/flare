<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Builders;

use App\Game\Character\CharacterInventory\Builders\EquipManyBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class EquipManyBuilderTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?EquipManyBuilder $equipManyBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->equipManyBuilder = resolve(EquipManyBuilder::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->equipManyBuilder = null;
    }

    public function test_ignores_slots_whose_item_type_is_not_equippable(): void
    {
        $item = $this->createItem(['type' => 'quest']);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $result = $this->equipManyBuilder->buildEquipmentArray($character, [$slotId]);

        $this->assertSame([], $result);
    }

    public function test_assigns_a_single_position_type_to_its_only_position(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $result = $this->equipManyBuilder->buildEquipmentArray($character, [$slotId]);

        $this->assertCount(1, $result);
        $this->assertSame('body', $result[0]['position']);
        $this->assertSame('body', $result[0]['equip_type']);
    }

    public function test_skips_a_second_item_when_the_single_position_type_is_already_taken(): void
    {
        $itemOne = $this->createItem(['type' => 'body']);
        $itemTwo = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()
            ->giveItem($itemOne)
            ->giveItem($itemTwo)
            ->getCharacter();
        $slotIds = $character->inventory->slots->pluck('id')->all();

        $result = $this->equipManyBuilder->buildEquipmentArray($character, $slotIds);

        $this->assertCount(1, $result);
    }

    public function test_assigns_two_weapons_to_left_and_right_hand(): void
    {
        $itemOne = $this->createItem(['type' => 'weapon']);
        $itemTwo = $this->createItem(['type' => 'weapon']);
        $character = $this->character->inventoryManagement()
            ->giveItem($itemOne)
            ->giveItem($itemTwo)
            ->getCharacter();
        $slotIds = $character->inventory->slots->pluck('id')->all();

        $result = $this->equipManyBuilder->buildEquipmentArray($character, $slotIds);

        $this->assertCount(2, $result);
        $this->assertSame(['left-hand', 'right-hand'], array_column($result, 'position'));
    }

    public function test_skips_a_third_weapon_when_both_hand_positions_are_taken(): void
    {
        $itemOne = $this->createItem(['type' => 'weapon']);
        $itemTwo = $this->createItem(['type' => 'weapon']);
        $itemThree = $this->createItem(['type' => 'weapon']);
        $character = $this->character->inventoryManagement()
            ->giveItem($itemOne)
            ->giveItem($itemTwo)
            ->giveItem($itemThree)
            ->getCharacter();
        $slotIds = $character->inventory->slots->pluck('id')->all();

        $result = $this->equipManyBuilder->buildEquipmentArray($character, $slotIds);

        $this->assertCount(2, $result);
    }

    public function test_removes_duplicate_positions_across_different_item_types(): void
    {
        $weapon = $this->createItem(['type' => 'weapon']);
        $shield = $this->createItem(['type' => 'shield']);
        $character = $this->character->inventoryManagement()
            ->giveItem($weapon)
            ->giveItem($shield)
            ->getCharacter();
        $slotIds = $character->inventory->slots->pluck('id')->all();

        $result = $this->equipManyBuilder->buildEquipmentArray($character, $slotIds);

        $positions = array_column($result, 'position');
        $this->assertSame($positions, array_unique($positions));
    }

    public function test_assigns_rings_to_ring_one_and_ring_two(): void
    {
        $ringOne = $this->createItem(['type' => 'ring']);
        $ringTwo = $this->createItem(['type' => 'ring']);
        $character = $this->character->inventoryManagement()
            ->giveItem($ringOne)
            ->giveItem($ringTwo)
            ->getCharacter();
        $slotIds = $character->inventory->slots->pluck('id')->all();

        $result = $this->equipManyBuilder->buildEquipmentArray($character, $slotIds);

        $this->assertSame(['ring-one', 'ring-two'], array_column($result, 'position'));
    }

    public function test_assigns_spell_items_to_spell_one_and_spell_two(): void
    {
        $spellOne = $this->createItem(['type' => 'spell-damage']);
        $spellTwo = $this->createItem(['type' => 'spell-healing']);
        $character = $this->character->inventoryManagement()
            ->giveItem($spellOne)
            ->giveItem($spellTwo)
            ->getCharacter();
        $slotIds = $character->inventory->slots->pluck('id')->all();

        $result = $this->equipManyBuilder->buildEquipmentArray($character, $slotIds);

        $this->assertSame(['spell-one', 'spell-two'], array_column($result, 'position'));
    }

    public function test_only_builds_equipment_for_the_requested_slot_ids(): void
    {
        $requested = $this->createItem(['type' => 'body']);
        $notRequested = $this->createItem(['type' => 'helmet']);
        $character = $this->character->inventoryManagement()
            ->giveItem($requested)
            ->giveItem($notRequested)
            ->getCharacter();
        $requestedSlotId = $character->inventory->slots()->where('item_id', $requested->id)->first()->id;

        $result = $this->equipManyBuilder->buildEquipmentArray($character, [$requestedSlotId]);

        $this->assertCount(1, $result);
        $this->assertSame('body', $result[0]['equip_type']);
    }
}
