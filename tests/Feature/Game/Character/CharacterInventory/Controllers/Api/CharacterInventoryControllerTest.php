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

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_get_character_inventory_api_request()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem())->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory', [
                'per_page' => 10,
                'page' => 1,
                'search_text' => '',
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertNotEmpty($jsonData['data']);
    }

    public function test_fail_to_get_api_item_details()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem())->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/item', [
                'slot_id' => 999999,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals("There's nothing here for that slot.", $jsonData['message']);
    }

    public function test_get_item_details()
    {
        $item = $this->createItem([
            'type' => 'sword',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slotId = $character->inventory->slots->first()->id;

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/item', [
                'slot_id' => $slotId,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($item->name, $jsonData['name']);
    }

    public function test_destroy_item()
    {
        $item = $this->createItem([
            'type' => WeaponTypes::SWORD,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/destroy', [
                'item_id' => $item->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Destroyed item: '.$item->affix_name.'.', $jsonData['message']);
    }

    public function test_destroy_all_items()
    {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/destroy-all');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Destroyed all items.', $jsonData['message']);
    }

    public function test_disenchant_all_items()
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

    public function test_move_item_to_set()
    {
        $item = $this->createItem([
            'type' => WeaponTypes::SWORD,
        ]);

        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(2, true)
            ->getCharacterFactory()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $inventorySetId = $character->inventorySets()->first()->id;
        $inventorySlotId = $character->inventory->slots()->first()->id;

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/move-to-set', [
                'move_to_set' => $inventorySetId,
                'slot_id' => $inventorySlotId,
            ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_rename_set()
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

    public function test_save_equipped_as_set()
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

    public function test_remove_item_from_set()
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

    public function test_empty_set()
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

    public function test_cannot_equip_item()
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

    public function test_equip_item()
    {
        $character = $this->character->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem([
                'type' => WeaponTypes::SWORD,
                'name' => 'To Replace',
            ]))
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::SWORD,
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

        $this->assertEquals('Item has been equipped.', $jsonData['message']);
    }

    public function test_unequip_set()
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

    public function test_unequip_item()
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

    public function test_when_unequip_all_unequip_the_set()
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

    public function test_unequip_all_non_set_items()
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

    public function test_equip_and_item_set()
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

    public function test_use_many_items()
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

    public function test_use_single_alchemy_item()
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

    public function test_destroy_alchemy_item()
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

        $this->assertEquals('Destroyed Alchemy Item: '.$item->affix_name.'.', $jsonData['message']);
    }

    public function test_destroy_all_alchemy_items()
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
