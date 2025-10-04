<?php

namespace Tests\Feature\Game\Character\CharacterInventory\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class ItemComparisonControllerTest extends TestCase
{
    use CreateGem, CreateItem, RefreshDatabase;

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

    public function test_get_error_for_trying_to_compare_item_you_do_not_have()
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/comparison', [
                'item_to_equip_type' => 'spell-damage',
                'slot_id' => 999,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Item not found in your inventory.', $jsonData['message']);
    }

    public function test_get_comparison_for_spell_damage_for_set_equipped_item()
    {
        $spellForEquipment = $this->createItem([
            'type' => 'spell-damage',
            'base_damage' => 100,
            'int_mod' => .25,
        ]);

        $spellToCompare = $this->createItem([
            'type' => 'spell-damage',
            'base_damage' => 300,
            'int_mod' => .55,
        ]);

        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($spellForEquipment, 0, 'spell-one', true)
            ->getCharacterFactory()
            ->inventoryManagement()
            ->giveItem($spellToCompare)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/comparison', [
                'item_to_equip_type' => 'spell-damage',
                'slot_id' => $character->inventory->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertNotEmpty($jsonData['details']);
    }

    public function test_fail_to_compare_item_from_chat_when_equipped()
    {
        $spellForEquipment = $this->createItem([
            'type' => 'spell-damage',
            'base_damage' => 100,
            'int_mod' => .25,
        ]);

        $spellToCompare = $this->createItem([
            'type' => 'spell-damage',
            'base_damage' => 300,
            'int_mod' => .55,
        ]);

        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($spellForEquipment, 0, 'spell-one', true)
            ->getCharacterFactory()
            ->inventoryManagement()
            ->giveItem($spellToCompare, true, 'spell-two')
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/comparison-from-chat', [
                'id' => $character->inventory->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Item is no longer in your inventory.', $jsonData['message']);
    }

    public function test_fail_to_compare_item_from_chat_when_does_not_exist()
    {
        $spellForEquipment = $this->createItem([
            'type' => 'spell-damage',
            'base_damage' => 100,
            'int_mod' => .25,
        ]);

        $spellToCompare = $this->createItem([
            'type' => 'spell-damage',
            'base_damage' => 300,
            'int_mod' => .55,
        ]);

        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($spellForEquipment, 0, 'spell-one', true)
            ->getCharacterFactory()
            ->inventoryManagement()
            ->giveItem($spellToCompare, true, 'spell-two')
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/comparison-from-chat', [
                'id' => 978,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Item does not exist  ...', $jsonData['message']);
    }

    public function test_get_chat_comparison_data_for_item_in_inventory()
    {
        $spellForEquipment = $this->createItem([
            'type' => 'spell-damage',
            'base_damage' => 100,
            'int_mod' => .25,
        ]);

        $spellToCompare = $this->createItem([
            'type' => 'spell-damage',
            'base_damage' => 300,
            'int_mod' => .55,
        ]);

        $character = $this->character->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($spellForEquipment, 0, 'spell-one', true)
            ->getCharacterFactory()
            ->inventoryManagement()
            ->giveItem($spellToCompare)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/comparison-from-chat', [
                'id' => $character->inventory->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertNotEmpty($jsonData['comparison_data']['details']);
    }

    public function test_compare_two_gems()
    {
        $gemInInventory = $this->createGem(['primary_atonement_amount' => 0.4]);
        $gemInInventoryToCompare = $this->createGem(['primary_atonement_amount' => 0.5]);

        $character = $this->character->gemBagManagement()
            ->assignGemToBag($gemInInventory->id, 3)
            ->assignGemToBag($gemInInventoryToCompare->id)
            ->getCharacter();

        $gemSlotId = $character->gemBag->gemSlots->filter(function ($slot) use ($gemInInventoryToCompare) {
            return $slot->gem_id === $gemInInventoryToCompare->id;
        })->first()->id;

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/comparison-from-chat', [
                'id' => $gemSlotId,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertNotEmpty($jsonData['comparison_data']['itemToEquip']['item']);
        $this->assertEquals('gem', $jsonData['comparison_data']['itemToEquip']['type']);
    }
}
