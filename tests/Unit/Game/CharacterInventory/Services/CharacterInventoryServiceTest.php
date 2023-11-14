<?php

namespace Tests\Unit\Game\CharacterInventory\Services;

use App\Flare\Values\WeaponTypes;
use App\Game\CharacterInventory\Services\CharacterGemBagService;
use App\Game\CharacterInventory\Services\CharacterInventoryService;
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
}
