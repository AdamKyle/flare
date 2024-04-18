<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\ItemSkill;
use App\Flare\Values\WeaponTypes;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CharacterInventoryServiceTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateItemAffix;

    private ?CharacterFactory $character;

    private ?CharacterInventoryService $characterInventoryService;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $this->characterInventoryService = resolve(CharacterInventoryService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;

        $this->characterInventoryService = null;
    }

    public function tesGetInventoryForApi() {
        $character = $this->character->inventorySetManagement()
                                     ->createInventorySets()
                                     ->getCharacterFactory()
                                     ->equipStartingEquipment()
                                     ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForApi();

        $this->assertNotEmpty($result['equipped']);
        $this->assertNotEmpty($result['savable_sets']);
    }

    public function testGetInventoryForTypeSavableSets() {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->getCharacterFactory()
            ->equipStartingEquipment()
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('savable_sets');

        $this->assertNotEmpty($result);
    }

    public function testGetInventoryForTypeEquipped() {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->getCharacterFactory()
            ->equipStartingEquipment()
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('equipped');

        $this->assertNotEmpty($result);
    }

    public function testGetInventoryForTypeSets() {
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

    public function testGetInventoryForQuestItems() {
        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => 'quest'
            ]))
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('quest_items');

        $this->assertNotEmpty($result);
    }

    public function testGetInventoryForUsableItems() {
        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => 'alchemy',
                'usable' => true,
            ]))
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('usable_items');

        $this->assertNotEmpty($result);
    }

    public function testGetInventoryDataWhenNoValidTypePassedIn() {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->getCharacterFactory()
            ->equipStartingEquipment()
            ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->getInventoryForType('something');

        $this->assertNotEmpty($result['equipped']);
        $this->assertNotEmpty($result['savable_sets']);
    }

    public function testDisenchantAllItemsInInventory() {
        $character = $this->character->inventoryManagement()->giveItemMultipleTimes($this->createItem([
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix'])->id,
        ]), 75)->getCharacter();

        $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING)->first()->update([
            'xp_max' => 1,
        ]);

        $character = $character->refresh();

        $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING)->first()->update([
            'xp_max' => 1,
        ]);

        $character = $character->refresh();

        $result = $this->characterInventoryService->setCharacter($character)->disenchantAllItems($character->inventory->slots, $character);

        $this->assertEquals(200, $result['status']);

        $this->assertTrue(str_contains($result['message'], 'Skill Levels in Disenchanting.'));
        $this->assertTrue(str_contains($result['message'], 'Skill Levels in Enchanting.'));
    }

    public function testGetItemFromInventorySet() {
        $item = $this->createItem();

        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($item, 0)->getCharacter();

        $this->assertEquals($item->id, $this->characterInventoryService->getSlotForItemDetails($character, $item)->item_id);
    }

    public function testIncludeNamedSets() {
        $item = $this->createItem();

        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($item, 0)->getCharacter();

        $character->inventorySets()->first()->update([
            'name' => 'Sample'
        ]);

        $character = $character->refresh();

        $sets = $this->characterInventoryService->setCharacter($character)->getCharacterInventorySets();

        $this->assertTrue(array_key_exists('Sample', $sets));
    }

    public function testGetNoNameForNoEquippedSet() {
        $character = $this->character->getCharacter();

        $name = $this->characterInventoryService->setCharacter($character)->getEquippedInventorySetName();

        $this->assertNull($name);
    }

    public function testGetNameForNameEquippedSet() {
        $item = $this->createItem();

        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($item, 0, 'left-hand', true)->getCharacter();

        $character->inventorySets()->first()->update([
            'name' => 'Sample'
        ]);

        $character = $character->refresh();

        $name = $this->characterInventoryService->setCharacter($character)->getEquippedInventorySetName();

        $this->assertEquals('Sample', $name);
    }

    public function testGetNameForNonNamedSetEquipped() {
        $item = $this->createItem();

        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($item, 0, 'left-hand', true)->getCharacter();

        $name = $this->characterInventoryService->setCharacter($character)->getEquippedInventorySetName();

        $this->assertEquals('Set 1', $name);
    }

    public function testGetCharacterInventorySlotIds() {
        $alchemyItem = $this->createItem(['type' => 'alchemy']);
        $questItem = $this->createItem(['type' => 'quest']);
        $regularItem =  $this->createItem(['type' => WeaponTypes::WEAPON]);

        $character = $this->character->inventoryManagement()->giveItem($alchemyItem)->giveItem($questItem)->giveItem($regularItem)->getCharacter();

        $this->assertCount(1, $this->characterInventoryService->setCharacter($character)->findCharacterInventorySlotIds());
    }

    public function testFetchEquippedSetWithName() {
        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($this->createItem(), 0, 'left-hand', true)->getCharacter();

        $character->inventorySets()->first()->update([
            'name' => 'Sample'
        ]);

        $character = $character->refresh();

        $this->assertNotEmpty($this->characterInventoryService->setCharacter($character)->fetchEquipped());
    }

    public function testFetchEquippedSetWithNoName() {
        $character = $this->character->inventorySetManagement()->createInventorySets()->putItemInSet($this->createItem(), 0, 'left-hand', true)->getCharacter();

        $this->assertNotEmpty($this->characterInventoryService->setCharacter($character)->fetchEquipped());
    }

    public function testFetchEquippedReturnsNull() {
        $character = $this->character->inventorySetManagement()->createInventorySets()->getCharacter();

        $this->assertEmpty($this->characterInventoryService->setCharacter($character)->fetchEquipped());
    }

    public function testCannotDeleteItemThatDoesntExist() {
        $character = $this->character->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->deleteItem(56788);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You don\'t own that item.', $result['message']);
    }

    public function testCannotDeleteItemThatIsEquipped() {

        $character = $this->character->inventoryManagement()->giveItem($this->createItem(), true, 'left_hand')->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->deleteItem($character->inventory->slots()->where('equipped', true)->first()->id);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Cannot destroy equipped item.', $result['message']);

    }

    public function testCanDeleteItemFromInventory() {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->deleteItem($character->inventory->slots->first()->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Destroyed ' . $item->affix_name . '.', $result['message']);
    }

    public function testCanDeleteArtifactWithItemSkillProgressionFromInventory() {
        $item = $this->createItem(['type' => 'artifact']);

        $itemSkill  = ItemSkill::create([
            'name'            => 'parent',
            'description'     => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $item->itemSkillProgressions()->create([
            'item_id' => $item->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 0,
            'current_kill' => 0,
            'is_training' => false,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->deleteItem($character->inventory->slots->first()->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Destroyed ' . $item->affix_name . '.', $result['message']);
    }

    public function testDeleteAllItemsInInventoryWithOutDestroyingUsableOrQuestItems() {
        $item = $this->createItem(['type' => 'artifact']);

        $itemSkill  = ItemSkill::create([
            'name'            => 'parent',
            'description'     => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $item->itemSkillProgressions()->create([
            'item_id' => $item->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 0,
            'current_kill' => 0,
            'is_training' => false,
        ]);

        $regularItem = $this->createItem();
        $questItem = $this->createItem(['type' => 'quest']);
        $alchemy = $this->createItem(['type' => 'alchemy']);

        $character = $this->character->inventoryManagement()
                ->giveItem($item)
                ->giveItem($regularItem)
                ->giveItem($questItem)
                ->giveItem($alchemy)
                ->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->destroyAllItemsInInventory();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Destroyed all items.', $result['message']);
        $this->assertCount(2, $result['inventory']['inventory']);
    }

    public function testDisenchantAllItemsHasNothingToDisenchant() {
        $character = $this->character->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->disenchantAllItemsInInventory();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('You have nothing to disenchant.', $result['message']);
    }

    public function testDisenchantAllItems() {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'item_suffix_id' => $this->createItemAffix(['type' => 'suffix'])
        ]))->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->disenchantAllItemsInInventory();

        $this->assertEquals(200, $result['status']);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
    }

    public function testCannotUnequipItemWhenInventoryIsFull() {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $character->update([
            'inventory_max' => 0,
        ]);

        $character = $character->refresh();

        $result = $this->characterInventoryService->setCharacter($character)->unequipItem(4);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Your inventory is full. Cannot unequip items. You have no room in your inventory.', $result['message']);
    }

    public function testCannotUnequipItemWhenItemDoesNotExist() {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->unequipItem(4);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No item found to be unequipped.', $result['message']);
    }

    public function testCanUnequipItem() {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $slot = $character->inventory->slots()->where('equipped', true)->first();

        $result = $this->characterInventoryService->setCharacter($character)->unequipItem($slot->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Unequipped item: ' . $slot->item->affix_name, $result['message']);

        $this->assertFalse($slot->refresh()->equipped);
    }

    public function testInventoryIsFullCannotUnequipItems() {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $character->update([
            'inventory_max' => 0,
        ]);

        $character = $character->refresh();

        $result = $this->characterInventoryService->setCharacter($character)->unequipAllItems();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Your inventory is full. Cannot unequip items. You have no room in your inventory.', $result['message']);
    }

    public function testCanUnequipAllItems() {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $character = $character->refresh();

        $result = $this->characterInventoryService->setCharacter($character)->unequipAllItems();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('All items have been unequipped.', $result['message']);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots()->where('equipped', true)->get());
    }

    public function testCannotDestroyAlchemyItemYouDoNotHave() {
        $character = $this->character->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->destroyAlchemyItem(1);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No alchemy item found to destroy.', $result['message']);
    }

    public function testCanDeleteAlchemyItem() {
        $alchemyItem = $this->createItem([
            'type' => 'alchemy',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($alchemyItem)->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->destroyAlchemyItem($character->inventory->slots->where('item.type', '=', 'alchemy')->first()->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Destroyed Alchemy Item: ' . $alchemyItem->name . '.', $result['message']);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots()->where('equipped', true)->get());
    }

    public function testCanDeleteAllAlchemyItem() {
        $alchemyItem = $this->createItem([
            'type' => 'alchemy',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($alchemyItem)->getCharacter();

        $result = $this->characterInventoryService->setCharacter($character)->destroyAllAlchemyItems();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Destroyed All Alchemy Items.', $result['message']);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots()->where('equipped', true)->get());
    }
}
