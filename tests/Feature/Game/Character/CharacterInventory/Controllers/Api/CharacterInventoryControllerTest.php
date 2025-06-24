<?php

namespace Tests\Feature\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Values\WeaponTypes;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CharacterInventoryControllerTest extends TestCase
{
    use CreateGem, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetCharacterInventoryApiRequest()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem())->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory', [
                'per_page' => 10,
                'page'     => 1,
                'search_text' => '',
            ]);

        $jsonData = json_decode($response->getContent(), true);


        $this->assertNotEmpty($jsonData['data']);
    }

    public function testFailToGetApiItemDetails()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem())->getCharacter();

        $item = $this->createItem();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/item/'.$item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals("There's nothing here for that slot.", $jsonData['message']);
    }

    public function testGetItemDetails()
    {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/item/'.$item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($item->name, $jsonData['name']);
    }

    public function testDestroyItem()
    {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/destroy', [
                'slot_id' => $character->inventory->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Destroyed '.$item->affix_name.'.', $jsonData['message']);
    }

    public function testDestroyAllItems()
    {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/destroy-all');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Destroyed all items.', $jsonData['message']);
    }

    public function testDisenchantAllItems()
    {
        $item = $this->createItem([
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
            ]),
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/disenchant-all');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertTrue(str_contains($jsonData['message'], 'Disenchanted all items and gained'));

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
    }

    public function testMoveItemToSet()
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacterFactory()->inventorySetManagement()->createInventorySets(2, true)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/move-to-set', [
                'move_to_set' => $character->inventorySets->first()->id,
                'slot_id' => $character->inventory->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($item->affix_name.' Has been moved to: '.$character->inventorySets->first()->name, $jsonData['message']);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
    }

    public function testRenameSet()
    {
        $character = $this->character->inventorySetManagement()->createInventorySets()->getCharacter();

        $set = $character->inventorySets()->first();

        $set->update(['name' => 'sample']);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory-set/rename-set', [
                'set_id' => $character->inventorySets->first()->id,
                'set_name' => 'Apples',
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Renamed set to: Apples', $jsonData['message']);
    }

    public function testSaveEquippedAsSet()
    {
        $character = $this->character->equipStartingEquipment()->inventorySetManagement()->createInventorySets(1, true)->getCharacter();

        $set = $character->inventorySets->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/save-equipped-as-set', [
                'move_to_set' => $character->inventorySets->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($set->refresh()->name.' is now equipped (equipment has been moved to the set).', $jsonData['message']);
    }

    public function testRemoveItemFromSet()
    {
        $itemToRemove = $this->createItem();
        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10, true)
            ->putItemInSet($itemToRemove, 0)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory-set/remove', [
                'inventory_set_id' => $character->inventorySets->first()->id,
                'slot_id' => $character->inventorySets->first()->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $character = $character->refresh();

        $setName = $character->inventorySets->first()->name;

        $this->assertEquals('Removed '.$itemToRemove->affix_name.' from '.$setName.' and placed back into your inventory.', $jsonData['message']);
    }

    public function testEmptySet()
    {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $set = $character->inventorySets->first();

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory-set/'.$set->id.'/remove-all', [
                'inventory_set_id' => $character->inventorySets->first()->id,
                'slot_id' => $character->inventorySets->first()->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Removed '. 2 .' of '. 2 .' items from '.$set->name.'. If all items were not moved over, it is because your inventory became full.', $jsonData['message']);
    }

    public function testCannotEquipItem()
    {
        $character = $this->character->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'name' => 'To Replace',
            ]))
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'name' => 'Equipped',
            ]), 0, 'left-hand', true)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/equip-item', [
                'position' => 'left-hand',
                'equip_type' => $character->inventory->slots->first()->item->type,
                'slot_id' => 88477,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('The item you are trying to equip as a replacement, does not exist.', $jsonData['message']);
    }

    public function testEquipItem()
    {
        $character = $this->character->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'name' => 'To Replace',
            ]))
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'name' => 'Equipped',
            ]), 0, 'left-hand', true)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/equip-item', [
                'position' => 'left-hand',
                'equip_type' => $character->inventory->slots->first()->item->type,
                'slot_id' => $character->inventory->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Equipped item.', $jsonData['message']);
    }

    public function testUnequipSet()
    {

        $character = $this->character->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0, 'left-hand', true)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/unequip', [
                'inventory_set_equipped' => true,
                'item_to_remove' => $character->inventorySets->first()->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Unequipped '.$character->inventorySets->first()->name.'.', $jsonData['message']);
    }

    public function testUnequipItem()
    {

        $character = $this->character->equipStartingEquipment()
            ->getCharacter();

        $slot = $character->inventory->slots()->where('equipped', true)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/unequip', [
                'inventory_set_equipped' => false,
                'item_to_remove' => $slot->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Unequipped item: '.$slot->item->affix_name, $jsonData['message']);
    }

    public function testWhenUnequipAllUnequipTheSet()
    {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0, 'left-hand', true)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/unequip-all', [
                'is_set_equipped' => true,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Unequipped '.$character->inventorySets->first()->name.'.', $jsonData['message']);
    }

    public function testUnequipAllNonSetItems()
    {
        $character = $this->character->equipStartingEquipment()
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/unequip-all', [
                'is_set_equipped' => false,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('All items have been unequipped.', $jsonData['message']);
    }

    public function testEquipAndItemSet()
    {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $set = $character->inventorySets()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory-set/equip/'.$set->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($set->name.' is now equipped', $jsonData['message']);
    }

    public function testUseManyItems()
    {

        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/use-many-items', [
                'items_to_use' => [
                    $character->inventory->slots->first()->id,
                ],
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Used selected items.', $jsonData['message']);
    }

    public function testUseSingleAlchemyItem()
    {
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/use-item/'.$item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Used selected item.', $jsonData['message']);
    }

    public function testDestroyAlchemyItem()
    {
        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/destroy-alchemy-item', [
                'slot_id' => $character->inventory->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Destroyed Alchemy Item: '.$item->name.'.', $jsonData['message']);
    }

    public function testDestroyAllAlchemyItems()
    {
        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/destroy-all-alchemy-items', [
                'slot_id' => $character->inventory->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Destroyed All Alchemy Items.', $jsonData['message']);
    }
}
