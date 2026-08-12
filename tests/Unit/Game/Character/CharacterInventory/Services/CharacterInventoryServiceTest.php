<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\InventorySet;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Skills\Values\SkillTypeValue;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateItemSkill;
use Tests\Traits\CreateItemSkillProgression;

class CharacterInventoryServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateGem, CreateInventorySets, CreateItem, CreateItemAffix, CreateItemSkill, CreateItemSkillProgression, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterInventoryService $characterInventoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->characterInventoryService = resolve(CharacterInventoryService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->characterInventoryService = null;
    }

    public function test_get_inventory_for_api()
    {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->getCharacterFactory()
            ->equipStartingEquipment()
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForApi();

        $this->assertNotEmpty($result['equipped']);
        $this->assertNotEmpty($result['savable_sets']);
    }

    public function test_get_inventory_for_type_savable_sets()
    {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->getCharacterFactory()
            ->equipStartingEquipment()
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('savable_sets');

        $this->assertNotEmpty($result);
    }

    public function test_get_inventory_for_type_equipped()
    {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->getCharacterFactory()
            ->equipStartingEquipment()
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('equipped');

        $this->assertNotEmpty($result);
    }

    public function test_get_inventory_for_type_sets()
    {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets(2)
            ->putItemInSet($this->createItem(), 1, 'left-hand', true)
            ->getCharacterFactory()
            ->equipStartingEquipment()
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('sets');

        $this->assertNotEmpty($result['sets']);
        $this->assertTrue($result['set_equipped']);
    }

    public function test_get_inventory_for_quest_items()
    {
        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => 'quest',
            ]))
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('quest_items');

        $this->assertNotEmpty($result);
    }

    public function test_get_inventory_for_usable_items()
    {
        $item = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
        ]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('usable_items');

        $this->assertNotEmpty($result);
    }

    public function test_fetch_character_usable_items_paginates_without_error()
    {
        $item = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
        ]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $result = $this->characterInventoryService->setCharacter($character)->fetchCharacterUsableItems();

        $this->assertNotEmpty($result['data']);
        $this->assertSame($item->id, $result['data'][0]['item_id']);
    }

    public function test_get_inventory_data_when_no_valid_type_passed_in()
    {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->getCharacterFactory()
            ->equipStartingEquipment()
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('something');

        $this->assertNotEmpty($result['equipped']);
        $this->assertNotEmpty($result['savable_sets']);
    }

    public function test_disenchant_all_items_in_inventory()
    {
        $character = $this->character->inventoryManagement()->giveItemMultipleTimes($this->createItem([
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix'])->id,
        ]), 75)->getCharacter();

        $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $character = $character->refresh();

        $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first()->update([
            'xp_max' => 1,
        ]);

        $character = $character->refresh();

        $result = $this->characterInventoryService->setCharacter($character)->disenchantAllItems($character->inventory->slots, $character);

        $this->assertEquals(200, $result['status']);

        $this->assertTrue(str_contains($result['message'], 'Skill Levels in Disenchanting.'));
        $this->assertTrue(str_contains($result['message'], 'Skill Levels in Enchanting.'));
    }

    public function test_get_item_from_inventory_set()
    {
        $item = $this->createItem();

        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($item, 0)->getCharacter();

        $this->assertEquals($item->id, $this->characterInventoryService->getSlotForItemDetails($character, $item)->item_id);
    }

    public function test_get_set_slot_for_item_details_resolves_the_exact_set_slot()
    {
        $item = $this->createItem();

        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($item, 0)->getCharacter();

        $setSlot = $character->inventorySets()->first()->slots()->where('item_id', $item->id)->first();

        $result = $this->characterInventoryService->getSetSlotForItemDetails($character, $item, $setSlot->id);

        $this->assertNotNull($result);
        $this->assertSame($setSlot->id, $result->id);
    }

    public function test_include_named_sets()
    {
        $item = $this->createItem();

        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($item, 0)
            ->getCharacter();

        $character->inventorySets()->first()->update(['name' => 'Sample']);
        $character = $character->refresh();

        $sets = $this->characterInventoryService
            ->setCharacter($character)
            ->getCharacterInventorySets();

        $this->assertContains(
            'Sample',
            array_column($sets['data'], 'name')
        );
    }

    public function test_get_no_name_for_no_equipped_set()
    {
        $character = $this->character->getCharacter();

        $name = $this->characterInventoryService->setCharacter($character)->getEquippedInventorySetName();

        $this->assertNull($name);
    }

    public function test_get_name_for_name_equipped_set()
    {
        $item = $this->createItem();

        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($item, 0, 'left-hand', true)->getCharacter();

        $character->inventorySets()->first()->update([
            'name' => 'Sample',
        ]);

        $character = $character->refresh();

        $name = $this->characterInventoryService->setCharacter($character)->getEquippedInventorySetName();

        $this->assertEquals('Sample', $name);
    }

    public function test_get_name_for_non_named_set_equipped()
    {
        $item = $this->createItem();

        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($item, 0, 'left-hand', true)->getCharacter();

        $name = $this->characterInventoryService->setCharacter($character)->getEquippedInventorySetName();

        $this->assertEquals('Set 1', $name);
    }

    public function test_get_character_inventory_slot_ids()
    {
        $alchemyItem = $this->createItem(['type' => 'alchemy']);
        $questItem = $this->createItem(['type' => 'quest']);
        $regularItem = $this->createItem(['type' => ItemType::WEAPON->value]);

        $character = $this->character->inventoryManagement()->giveItem($alchemyItem)->giveItem($questItem)->giveItem($regularItem)->getCharacter();

        $this->assertCount(1, $this->characterInventoryService->setCharacter($character)->findCharacterInventorySlotIds());
    }

    public function test_fetch_equipped_set_with_name()
    {
        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($this->createItem(), 0, 'left-hand', true)->getCharacter();

        $character->inventorySets()->first()->update([
            'name' => 'Sample',
        ]);

        $character = $character->refresh();

        $this->assertNotEmpty($this->characterInventoryService->setCharacter($character)->fetchEquipped());
    }

    public function test_fetch_equipped_set_with_no_name()
    {
        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($this->createItem(), 0, 'left-hand', true)->getCharacter();

        $this->assertNotEmpty($this->characterInventoryService->setCharacter($character)->fetchEquipped());
    }

    public function test_fetch_equipped_returns_null()
    {
        $character = $this->character->inventorySetManagement()->createInventorySets()->getCharacter();

        $this->assertEmpty($this->characterInventoryService->setCharacter($character)->fetchEquipped());
    }

    public function test_cannot_delete_item_that_doesnt_exist()
    {
        $character = $this->character->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->deleteItem(56788);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No matching item to destroy.', $result['message']);
    }

    public function test_cannot_delete_item_that_is_equipped()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem(), true, 'left_hand')->getCharacter();

        $equippedSlot = $character->inventory->slots()->where('equipped', true)->first();

        $result = $this->characterInventoryService->setCharacter($character)->deleteItem($equippedSlot->item_id);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No matching item to destroy.', $result['message']);
    }

    public function test_can_delete_item_from_inventory()
    {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->deleteItem($item->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Destroyed item: '.$item->affix_name.'.', $result['message']);
    }

    public function test_can_delete_artifact_with_item_skill_progression_from_inventory()
    {
        $item = $this->createItem(['type' => 'artifact']);

        $itemSkill = $this->createItemSkill([
            'name' => 'parent',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $this->createItemSkillProgression([
            'item_id' => $item->id,
            'item_skill_id' => $itemSkill->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->deleteItem($item->id);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No matching item to destroy.', $result['message']);
    }

    public function test_delete_all_items_in_inventory_with_out_destroying_usable_or_quest_items_all_artifacts()
    {
        $artifact = $this->createItem(['type' => 'artifact']);

        $itemSkill = $this->createItemSkill([
            'name' => 'parent',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $this->createItemSkillProgression([
            'item_id' => $artifact->id,
            'item_skill_id' => $itemSkill->id,
        ]);

        $regularItem = $this->createItem();
        $questItem = $this->createItem(['type' => 'quest']);
        $alchemy = $this->createItem(['type' => 'alchemy']);

        $character = $this->character->inventoryManagement()
            ->giveItem($artifact)
            ->giveItem($regularItem)
            ->giveItem($questItem)
            ->giveItem($alchemy)
            ->getCharacter();

        $result = $this->characterInventoryService
            ->setCharacter($character)
            ->destroyAllItemsInInventory();

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Destroyed all items.', $result['message']);

        $this->assertArrayHasKey('data', $result['inventory']);
        $this->assertCount(1, $result['inventory']['data']);
        $this->assertEquals('artifact', $result['inventory']['data'][0]['type']);

        $this->assertCount(1, $character->inventory->slots->where('item.type', 'alchemy'));
    }

    public function test_disenchant_all_items_has_nothing_to_disenchant()
    {
        $character = $this->character->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->disenchantAllItemsInInventory();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('You have nothing to disenchant.', $result['message']);
    }

    public function test_disenchant_all_items()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'item_suffix_id' => $this->createItemAffix(['type' => 'suffix']),
        ]))->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->disenchantAllItemsInInventory();

        $this->assertEquals(200, $result['status']);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
    }

    public function test_cannot_unequip_item_when_inventory_is_full()
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $character->update([
            'inventory_max' => 0,
        ]);

        $character = $character->refresh();

        $result = $this->characterInventoryService->setCharacter($character)->unequipItem(4);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Your inventory is full. Cannot unequip items. You have no room in your inventory.', $result['message']);
    }

    public function test_cannot_unequip_item_when_item_does_not_exist()
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->unequipItem(4);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No item found to be unequipped.', $result['message']);
    }

    public function test_can_unequip_item()
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $slot = $character->inventory->slots()->where('equipped', true)->first();

        $result = $this->characterInventoryService->setCharacter($character)->unequipItem($slot->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Unequipped item: '.$slot->item->affix_name, $result['message']);

        $this->assertFalse($slot->refresh()->equipped);
    }

    public function test_inventory_is_full_cannot_unequip_items()
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $character->update([
            'inventory_max' => 0,
        ]);

        $character = $character->refresh();

        $result = $this->characterInventoryService->setCharacter($character)->unequipAllItems();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Your inventory is full. Cannot unequip items. You have no room in your inventory.', $result['message']);
    }

    public function test_can_unequip_all_items()
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $character = $character->refresh();

        $result = $this->characterInventoryService->setCharacter($character)->unequipAllItems();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('All items have been unequipped.', $result['message']);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots()->where('equipped', true)->get());
    }

    public function test_cannot_destroy_alchemy_item_you_do_not_have()
    {
        $character = $this->character->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->destroyAlchemyItem(1);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No alchemy item found to destroy.', $result['message']);
    }

    public function test_cannot_destroy_alchemy_item_when_character_has_no_alchemy_bag()
    {
        $character = $this->character->getCharacter();

        $character->alchemyBag()->delete();

        $result = $this->characterInventoryService->setCharacter($character->refresh())->destroyAlchemyItem(1);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No alchemy item found to destroy.', $result['message']);
    }

    public function test_can_delete_alchemy_item()
    {
        $alchemyItem = $this->createItem([
            'type' => 'alchemy',
        ]);

        $character = $this->character->getCharacter();
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $alchemyItem->id,
            'amount' => 2,
        ]);

        $result = $this->characterInventoryService->setCharacter($character)->destroyAlchemyItem($slot->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Destroyed Alchemy Item: '.$alchemyItem->name.'.', $result['message']);
        $this->assertEquals(0, $character->alchemyBag->slots()->where('id', $slot->id)->count());
    }

    public function test_can_delete_all_alchemy_item()
    {
        $firstAlchemyItem = $this->createItem([
            'type' => 'alchemy',
        ]);
        $secondAlchemyItem = $this->createItem([
            'type' => 'alchemy',
        ]);

        $character = $this->character->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $firstAlchemyItem->id,
            'amount' => 2,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $secondAlchemyItem->id,
            'amount' => 3,
        ]);
        $otherSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $otherCharacter->alchemyBag->id,
            'character_id' => $otherCharacter->id,
            'item_id' => $firstAlchemyItem->id,
            'amount' => 4,
        ]);

        $result = $this->characterInventoryService->setCharacter($character)->destroyAllAlchemyItems();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Destroyed All Alchemy Items.', $result['message']);
        $this->assertEquals(0, $character->alchemyBag->slots()->count());
        $this->assertEquals(4, $otherSlot->refresh()->amount);
    }

    public function test_destroy_all_alchemy_items_does_not_touch_normal_or_quest_inventory(): void
    {
        $normalItem = $this->createItem([
            'type' => ItemType::WEAPON->value,
        ]);
        $questItem = $this->createItem([
            'type' => 'quest',
        ]);
        $character = $this->character->inventoryManagement()
            ->giveItem($normalItem)
            ->giveItem($questItem)
            ->getCharacter();

        $this->characterInventoryService->setCharacter($character)->destroyAllAlchemyItems();

        $this->assertEquals(1, $character->inventory->slots()->where('item_id', $normalItem->id)->count());
        $this->assertEquals(1, $character->inventory->slots()->where('item_id', $questItem->id)->count());
    }

    public function test_destroy_all_alchemy_items_does_not_touch_gem_bag_slots(): void
    {
        $gem = $this->createGem();
        $character = $this->character->gemBagManagement()->assignGemStackToBag($gem->id, 2)->getCharacter();
        $gemSlot = $character->gemBag->gemSlots()->where('gem_id', $gem->id)->first();

        $this->characterInventoryService->setCharacter($character)->destroyAllAlchemyItems();

        $this->assertEquals(2, $gemSlot->refresh()->amount);
    }

    public function test_batch_crafting_set_appears_last_in_set_payload(): void
    {
        $character = $this->character->inventorySetManagement()->createInventorySets(1, true)->getCharacter();
        $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);

        $sets = $this->characterInventoryService->setCharacter($character->refresh())->getCharacterInventorySets();

        $lastSet = $sets['data'][array_key_last($sets['data'])];

        $this->assertSame(InventorySet::BATCH_CRAFTING_SET_NAME, $lastSet['name']);
        $this->assertTrue($lastSet['is_batch_crafting_set']);
    }

    public function test_batch_crafting_set_is_excluded_from_usable_sets(): void
    {
        $character = $this->character->inventorySetManagement()->createInventorySets(1, true)->getCharacter();
        $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);

        $sets = $this->characterInventoryService->setCharacter($character->refresh())->getUsableSets();

        $this->assertCount(1, $sets);
        $this->assertNotSame(InventorySet::BATCH_CRAFTING_SET_NAME, $sets[0]['name']);
    }

    public function test_batch_crafting_set_excluded_from_savable_sets(): void
    {
        $character = $this->character->inventorySetManagement()->createInventorySets(1, true)->getCharacter();
        $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);

        $savableSets = $this->characterInventoryService->setCharacter($character->refresh())->getInventoryForType('savable_sets');

        $batchCraftingSetIncluded = collect($savableSets)->contains(fn ($set) => ($set['name'] ?? null) === InventorySet::BATCH_CRAFTING_SET_NAME);

        $this->assertFalse($batchCraftingSetIncluded);
    }

    public function test_set_inventory_returns_regular_inventory_slots_matching_the_configured_positions(): void
    {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item, false, 'left-hand')->getCharacter();

        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $inventory = $this->characterInventoryService
            ->setCharacter($character)
            ->setInventorySlot($slot)
            ->setPositions(['left-hand'])
            ->setInventory()
            ->inventory();

        $this->assertCount(1, $inventory);
        $this->assertTrue($inventory->contains('id', $slot->id));
    }

    public function test_set_inventory_falls_back_to_equipped_set_slots_matching_the_configured_positions(): void
    {
        $item = $this->createItem();

        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($item, 0, 'left-hand', true)
            ->getCharacter();

        $inventory = $this->characterInventoryService
            ->setCharacter($character)
            ->setPositions(['left-hand'])
            ->setInventory()
            ->inventory();

        $this->assertCount(1, $inventory);
        $this->assertSame($item->id, $inventory->first()->item_id);
    }

    public function test_get_type_normalizes_armour_positions_to_armour(): void
    {
        $item = $this->createItem(['type' => 'body']);

        $character = $this->character->getCharacter();

        $type = $this->characterInventoryService->setCharacter($character)->getType($item);

        $this->assertSame('armour', $type);
    }

    public function test_get_type_normalizes_spell_damage_to_spell(): void
    {
        $item = $this->createItem(['type' => 'spell-damage']);

        $character = $this->character->getCharacter();

        $type = $this->characterInventoryService->setCharacter($character)->getType($item);

        $this->assertSame('spell', $type);
    }

    public function test_get_type_returns_accepted_type_unchanged(): void
    {
        $item = $this->createItem(['type' => 'ring']);

        $character = $this->character->getCharacter();

        $type = $this->characterInventoryService->setCharacter($character)->getType($item);

        $this->assertSame('ring', $type);
    }

    public function test_get_type_throws_exception_for_unknown_item_type(): void
    {
        $item = $this->createItem(['type' => 'not-a-real-type']);

        $character = $this->character->getCharacter();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown Item type: not-a-real-type');

        $this->characterInventoryService->setCharacter($character)->getType($item);
    }

    public function test_get_set_items_filters_by_provided_set_id(): void
    {
        $matchingItem = $this->createItem();
        $otherItem = $this->createItem();

        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(2)
            ->putItemInSet($matchingItem, 0)
            ->putItemInSet($otherItem, 1)
            ->getCharacter();

        $matchingSetId = $character->inventorySets()->orderBy('id')->first()->id;

        $result = $this->characterInventoryService
            ->setCharacter($character)
            ->getSetItems(10, 1, '', ['set_id' => $matchingSetId]);

        $this->assertCount(1, $result['data']);
        $this->assertSame($matchingItem->id, $result['data'][0]['item_id']);
    }

    public function test_get_set_items_defaults_to_the_equipped_set_when_no_set_id_filter_given(): void
    {
        $item = $this->createItem();

        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($item, 0, 'left-hand', true)
            ->getCharacter();

        $result = $this->characterInventoryService
            ->setCharacter($character)
            ->getSetItems();

        $this->assertCount(1, $result['data']);
        $this->assertSame($item->id, $result['data'][0]['item_id']);
    }

    public function test_get_set_items_filters_by_search_text_matching_item_name(): void
    {
        $matchingItem = $this->createItem(['name' => 'Sunfire Blade']);
        $otherItem = $this->createItem(['name' => 'Moonshadow Bow']);

        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($matchingItem, 0, null, true)
            ->putItemInSet($otherItem, 0)
            ->getCharacter();

        $result = $this->characterInventoryService
            ->setCharacter($character)
            ->getSetItems(10, 1, 'sunfire');

        $this->assertCount(1, $result['data']);
        $this->assertSame($matchingItem->id, $result['data'][0]['item_id']);
    }

    public function test_get_set_items_filters_by_search_text_matching_item_prefix(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Blazing', 'type' => 'prefix']);
        $matchingItem = $this->createItem(['name' => 'Sword', 'item_prefix_id' => $prefix->id]);
        $otherItem = $this->createItem(['name' => 'Axe']);

        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($matchingItem, 0, null, true)
            ->putItemInSet($otherItem, 0)
            ->getCharacter();

        $result = $this->characterInventoryService
            ->setCharacter($character)
            ->getSetItems(10, 1, 'blazing');

        $this->assertCount(1, $result['data']);
        $this->assertSame($matchingItem->id, $result['data'][0]['item_id']);
    }

    public function test_get_set_items_filters_by_search_text_matching_item_suffix(): void
    {
        $suffix = $this->createItemAffix(['name' => 'of the Fox', 'type' => 'suffix']);
        $matchingItem = $this->createItem(['name' => 'Sword', 'item_suffix_id' => $suffix->id]);
        $otherItem = $this->createItem(['name' => 'Axe']);

        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($matchingItem, 0, null, true)
            ->putItemInSet($otherItem, 0)
            ->getCharacter();

        $result = $this->characterInventoryService
            ->setCharacter($character)
            ->getSetItems(10, 1, 'fox');

        $this->assertCount(1, $result['data']);
        $this->assertSame($matchingItem->id, $result['data'][0]['item_id']);
    }

    public function test_get_usable_items_returns_empty_when_character_has_no_alchemy_bag(): void
    {
        $character = $this->character->getCharacter();

        $character->alchemyBag()->delete();

        $result = $this->characterInventoryService->setCharacter($character->refresh())->getUsableItems();

        $this->assertSame([], $result);
    }

    public function test_get_usable_items_filters_by_search_text(): void
    {
        $matchingItem = $this->createItem(['type' => 'alchemy', 'usable' => true, 'name' => 'Healing Draught']);
        $otherItem = $this->createItem(['type' => 'alchemy', 'usable' => true, 'name' => 'Poison Vial']);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterInventoryService->setCharacter($character)->getUsableItems('healing');

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_usable_items_filters_by_increase_stats(): void
    {
        $matchingItem = $this->createItem(['type' => 'alchemy', 'usable' => true, 'increase_stat_by' => 5]);
        $otherItem = $this->createItem(['type' => 'alchemy', 'usable' => true]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterInventoryService->setCharacter($character)->getUsableItems('', ['increase-stats' => true]);

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_usable_items_filters_by_effects_skills(): void
    {
        $matchingItem = $this->createItem(['type' => 'alchemy', 'usable' => true, 'increase_skill_bonus_by' => 5]);
        $otherItem = $this->createItem(['type' => 'alchemy', 'usable' => true]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterInventoryService->setCharacter($character)->getUsableItems('', ['effects-skills' => true]);

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_usable_items_filters_by_effects_base_modifiers(): void
    {
        $matchingItem = $this->createItem(['type' => 'alchemy', 'usable' => true, 'base_damage_mod' => 0.1]);
        $otherItem = $this->createItem(['type' => 'alchemy', 'usable' => true]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterInventoryService->setCharacter($character)->getUsableItems('', ['effects-base-modifiers' => true]);

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_usable_items_filters_by_damages_kingdoms(): void
    {
        $matchingItem = $this->createItem(['type' => 'alchemy', 'usable' => true, 'damages_kingdoms' => true]);
        $otherItem = $this->createItem(['type' => 'alchemy', 'usable' => true]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterInventoryService->setCharacter($character)->getUsableItems('', ['damages-kingdoms' => true]);

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_usable_items_filters_by_holy_oils(): void
    {
        $matchingItem = $this->createItem(['type' => 'alchemy', 'usable' => true, 'holy_level' => 1]);
        $otherItem = $this->createItem(['type' => 'alchemy', 'usable' => true]);

        $character = $this->character->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $matchingItem->id,
            'amount' => 1,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $otherItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterInventoryService->setCharacter($character)->getUsableItems('', ['holy-oils' => true]);

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result[0]['item_id']);
    }

    public function test_get_quest_items_filters_by_search_text(): void
    {
        $matchingItem = $this->createItem(['type' => 'quest', 'name' => 'ancient key']);
        $otherItem = $this->createItem(['type' => 'quest', 'name' => 'rusty coin']);

        $character = $this->character->inventoryManagement()
            ->giveItem($matchingItem)
            ->giveItem($otherItem)
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getQuestItems('ancient');

        $this->assertCount(1, $result);
        $this->assertSame($matchingItem->id, $result->first()->id);
    }

    public function test_fetch_character_quest_items_paginates_quest_items(): void
    {
        $item = $this->createItem(['type' => 'quest']);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->fetchCharacterQuestItems();

        $this->assertCount(1, $result['data']);
        $this->assertSame($item->id, $result['data'][0]['item_id']);
    }

    public function test_get_inventory_collection_filters_by_search_text_matching_item_name(): void
    {
        $matchingItem = $this->createItem(['name' => 'Sunfire Blade']);
        $otherItem = $this->createItem(['name' => 'Moonshadow Bow']);

        $character = $this->character->inventoryManagement()
            ->giveItem($matchingItem)
            ->giveItem($otherItem)
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->fetchCharacterInventory(10, 1, 'sunfire');

        $this->assertCount(1, $result['data']);
        $this->assertSame($matchingItem->id, $result['data'][0]['item_id']);
    }

    public function test_get_inventory_collection_filters_by_search_text_matching_item_prefix(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Blazing', 'type' => 'prefix']);
        $matchingItem = $this->createItem(['name' => 'Sword', 'item_prefix_id' => $prefix->id]);
        $otherItem = $this->createItem(['name' => 'Axe']);

        $character = $this->character->inventoryManagement()
            ->giveItem($matchingItem)
            ->giveItem($otherItem)
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->fetchCharacterInventory(10, 1, 'blazing');

        $this->assertCount(1, $result['data']);
        $this->assertSame($matchingItem->id, $result['data'][0]['item_id']);
    }

    public function test_get_inventory_collection_filters_by_search_text_matching_item_suffix(): void
    {
        $suffix = $this->createItemAffix(['name' => 'of the Fox', 'type' => 'suffix']);
        $matchingItem = $this->createItem(['name' => 'Sword', 'item_suffix_id' => $suffix->id]);
        $otherItem = $this->createItem(['name' => 'Axe']);

        $character = $this->character->inventoryManagement()
            ->giveItem($matchingItem)
            ->giveItem($otherItem)
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->fetchCharacterInventory(10, 1, 'fox');

        $this->assertCount(1, $result['data']);
        $this->assertSame($matchingItem->id, $result['data'][0]['item_id']);
    }

    public function test_disenchant_all_items_in_inventory_ignores_items_that_are_not_eligible_for_disenchanting(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $eligibleItem = $this->createItem(['item_prefix_id' => $prefix->id]);
        $questItem = $this->createItem(['type' => 'quest', 'item_prefix_id' => $prefix->id]);

        $character = $this->character->inventoryManagement()
            ->giveItem($eligibleItem)
            ->giveItem($questItem)
            ->getCharacter();

        $this->characterInventoryService->setCharacter($character)->disenchantAllItemsInInventory();

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots->where('item.type', 'quest'));
        $this->assertCount(0, $character->inventory->slots->where('item_id', $eligibleItem->id));
    }

    public function test_cannot_sell_item_that_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->sellItem(56788);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No item found to be sell.', $result['message']);
    }

    public function test_can_sell_item_from_inventory(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'cost' => 100]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $result = $this->characterInventoryService->setCharacter($character)->sellItem($item->id);

        $this->assertEquals(200, $result['status']);
        $this->assertTrue(str_contains($result['message'], 'Sold '.$item->affix_name.' for a total of'));
        $this->assertSame(0, $character->inventory->slots()->where('id', $slotId)->count());
    }

    public function test_cannot_disenchant_item_that_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->disenchantItem(56788);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No item found to disenchant.', $result['message']);
    }

    public function test_can_disenchant_item_from_inventory(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $item = $this->createItem(['item_prefix_id' => $prefix->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $result = $this->characterInventoryService->setCharacter($character)->disenchantItem($item->id);

        $this->assertEquals(200, $result['status']);
        $this->assertSame(0, $character->inventory->slots()->where('id', $slotId)->count());
    }
}
