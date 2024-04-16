<?php

namespace Tests\Feature\Game\Character\CharacterInventory\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CharacterInventoryControllerTest extends TestCase {

    use RefreshDatabase, CreateGem, CreateItem, CreateItemAffix;

    private ?CharacterFactory $character = null;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetCharacterInventoryApiRequest() {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem())->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertNotEmpty($jsonData['inventory']);
    }

    public function testFailToGetApiItemDetails() {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem())->getCharacter();

        $item = $this->createItem();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/item/' . $item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('You cannot do that.', $jsonData['message']);
    }

    public function testGetItemDetails() {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/item/' . $item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($item->name, $jsonData['name']);
    }

    public function testDestroyItem() {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/destroy', [
                'slot_id' => $character->inventory->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Destroyed ' . $item->affix_name . '.', $jsonData['message']);
    }

    public function testDestroyAllItems() {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/destroy-all');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Destroyed all items.', $jsonData['message']);
    }

    public function  testDisenchantAllItems() {
        $item = $this->createItem([
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix'
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

    public function  testMoveItemToSet() {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacterFactory()->inventorySetManagement()->createInventorySets(2, true)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/move-to-set', [
                'move_to_set' => $character->inventorySets->first()->id,
                'slot_id' => $character->inventory->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($item->affix_name . ' Has been moved to: ' . $character->inventorySets->first()->name, $jsonData['message']);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
    }

    public function testRenameSet() {
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

    public function testSaveEquippedAsSet() {
        $character = $this->character->equipStartingEquipment()->inventorySetManagement()->createInventorySets(1, true)->getCharacter();

        $set = $character->inventorySets->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/save-equipped-as-set', [
                'move_to_set' => $character->inventorySets->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($set->refresh()->name . ' is now equipped (equipment has been moved to the set).', $jsonData['message']);
    }

    public function testRemoveItemFromSet() {
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

        $this->assertEquals('Removed ' . $itemToRemove->affix_name . ' from ' . $setName . ' and placed back into your inventory.', $jsonData['message']);
    }

    public function testEmptySet() {
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
        
        $this->assertEquals('Removed ' . 2 . ' of ' . 2 . ' items from ' . $set->name . '. If all items were not moved over, it is because your inventory became full.', $jsonData['message']);
    }
}
