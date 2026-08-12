<?php

namespace Tests\Feature\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Models\InventorySet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;

class ItemComparisonControllerTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateInventorySets, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

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

    public function test_compare_item_returns_comparison_data_for_an_owned_slot(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/comparison?slot_id='.$slotId);

        $response->assertOk();
        $this->assertSame($slotId, $response->json('slotId'));
    }

    public function test_compare_item_returns_usable_item_details_for_an_alchemy_item(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'usable' => true]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/comparison?slot_id='.$slotId);

        $response->assertOk();
        $this->assertSame('alchemy', $response->json('type'));
    }

    public function test_compare_item_returns_details_against_the_currently_equipped_item_in_that_position(): void
    {
        $equipped = $this->createItem(['type' => 'sword']);
        $unequipped = $this->createItem(['type' => 'sword']);
        $character = $this->character->inventoryManagement()
            ->giveItem($equipped, true, 'left-hand')
            ->giveItem($unequipped)
            ->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $unequipped->id)->first()->id;

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/comparison?slot_id='.$slotId.'&item_to_equip_type=sword');

        $response->assertOk();
        $this->assertArrayHasKey('slotPosition', $response->json());
    }

    public function test_compare_item_returns_422_when_slot_not_in_inventory(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/comparison?slot_id=999999');

        $response->assertStatus(422);
        $this->assertSame('Item not found in your inventory.', $response->json('message'));
    }

    public function test_compare_item_from_chat_returns_alchemy_bag_comparison_data(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'usable' => true]);
        $character = $this->character->getCharacter();
        $alchemyBagSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/comparison-from-chat?id='.$alchemyBagSlot->id.'&source=alchemy_bag');

        $response->assertOk();
        $this->assertArrayHasKey('comparison_data', $response->json());
    }

    public function test_compare_item_from_chat_returns_404_for_missing_alchemy_bag_slot(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/comparison-from-chat?id=999999&source=alchemy_bag');

        $response->assertStatus(404);
    }

    public function test_compare_item_from_chat_returns_crafted_items_set_comparison_data(): void
    {
        $item = $this->createItem(['type' => 'weapon']);
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/comparison-from-chat?id='.$setSlot->id.'&source=crafted_items_set');

        $response->assertOk();
        $this->assertArrayHasKey('comparison_data', $response->json());
    }

    public function test_compare_item_from_chat_returns_404_for_missing_crafted_items_set_slot(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/comparison-from-chat?id=999999&source=crafted_items_set');

        $response->assertStatus(404);
    }

    public function test_compare_item_from_chat_returns_inventory_item_comparison_data(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/comparison-from-chat?id='.$slotId);

        $response->assertOk();
        $this->assertArrayHasKey('comparison_data', $response->json());
        $this->assertArrayHasKey('usable_sets', $response->json());
    }

    public function test_compare_item_from_chat_returns_404_when_item_is_already_equipped(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()->giveItem($item, true, 'body')->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/comparison-from-chat?id='.$slotId);

        $response->assertStatus(404);
        $this->assertSame('Item is no longer in your inventory.', $response->json('message'));
    }

    public function test_compare_item_normalizes_spell_damage_type_to_spell(): void
    {
        $item = $this->createItem(['type' => 'spell-damage']);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/comparison?slot_id='.$slotId.'&item_to_equip_type=spell-damage');

        $response->assertOk();
        $this->assertSame('spell', $response->json('type'));
    }

    public function test_compare_item_from_chat_normalizes_spell_healing_type_to_spell(): void
    {
        $item = $this->createItem(['type' => 'spell-healing']);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/comparison-from-chat?id='.$slotId);

        $response->assertOk();
        $this->assertSame('spell', $response->json('comparison_data.type'));
    }

    public function test_compare_item_from_chat_returns_gem_data_when_id_matches_a_gem_slot(): void
    {
        $character = $this->character->gemBagManagement()->assignGemsToBag()->getCharacter();
        $gemSlot = $character->gemBag->gemSlots()->first();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/comparison-from-chat?id='.$gemSlot->id);

        $response->assertOk();
        $this->assertSame('gem', $response->json('comparison_data.itemToEquip.type'));
    }

    public function test_compare_item_from_chat_returns_404_when_nothing_matches(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/comparison-from-chat?id=999999');

        $response->assertStatus(404);
        $this->assertSame('Item does not exist  ...', $response->json('message'));
    }
}
