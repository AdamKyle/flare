<?php

namespace Tests\Feature\Game\Shop\Controllers\Api;

use App\Flare\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\InventoryManagement;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ShopControllerTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
    }

    protected function tearDown(): void
    {
        $this->character = null;

        parent::tearDown();
    }

    public function test_visit_shop_returns_standard_paginated_response_shape(): void
    {
        $item = $this->createItem(['type' => 'shield', 'cost' => 100]);

        $response = $this->actingAs($this->character->user)
            ->getJson('/api/character/'.$this->character->id.'/visit-shop?per_page=10&page=1&search_text=&filters[type]=shield');

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('can_load_more', $data['meta']);
        $this->assertSame(100, $data['data'][0]['cost']);
        $this->assertSame($item->id, $data['data'][0]['id']);
        $this->assertSame($item->id, $data['data'][0]['item_id']);
    }

    public function test_visit_shop_filters_by_type_filter_param(): void
    {
        $this->createItem(['type' => 'shield']);
        $this->createItem(['type' => 'hammer']);

        $response = $this->actingAs($this->character->user)
            ->getJson('/api/character/'.$this->character->id.'/visit-shop?per_page=10&page=1&search_text=&filters[type]=hammer');

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertCount(1, $data['data']);
        $this->assertSame('hammer', $data['data'][0]['type']);
    }

    public function test_visit_shop_sorts_by_sort_cost_filter_param(): void
    {
        $this->createItem(['type' => 'shield', 'cost' => 500]);
        $this->createItem(['type' => 'shield', 'cost' => 100]);

        $response = $this->actingAs($this->character->user)
            ->getJson('/api/character/'.$this->character->id.'/visit-shop?per_page=10&page=1&search_text=&filters[type]=shield&filters[sort_cost]=desc');

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame(500, $data['data'][0]['cost']);
        $this->assertSame(100, $data['data'][1]['cost']);
    }

    public function test_buy_returns_error_when_character_has_no_gold(): void
    {
        $this->character->update(['gold' => 0]);
        $item = $this->createItem(['cost' => 100]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/shop/buy/item/'.$this->character->id, ['item_id' => $item->id]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'You do not have enough gold.']);
    }

    public function test_buy_returns_error_when_item_not_found(): void
    {
        $this->character->update(['gold' => 1000]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/shop/buy/item/'.$this->character->id, ['item_id' => 999999]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Item not found.']);
    }

    public function test_buy_returns_error_when_not_enough_gold_for_item(): void
    {
        $this->character->update(['gold' => 50]);
        $item = $this->createItem(['cost' => 100]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/shop/buy/item/'.$this->character->id, ['item_id' => $item->id]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'You do not have enough gold.']);
    }

    public function test_buy_purchases_item_successfully(): void
    {
        $this->character->update(['gold' => 1000]);
        $item = $this->createItem(['cost' => 100, 'type' => 'shield']);

        $shopListResponse = $this->actingAs($this->character->user)
            ->getJson('/api/character/'.$this->character->id.'/visit-shop?per_page=10&page=1&search_text=&filters[type]=shield');

        $shopListData = json_decode($shopListResponse->getContent(), true);
        $listedItemId = $shopListData['data'][0]['item_id'];

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/shop/buy/item/'.$this->character->id, ['item_id' => $listedItemId]);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame($item->id, $listedItemId);
        $this->assertStringStartsWith('Purchased:', $jsonData['message']);
        $this->assertSame(900, $jsonData['gold']);
        $this->assertSame(
            $this->character->refresh()->inventory_max,
            $jsonData['inventory_count']['inventory_max']
        );
        $this->assertSame(1, $jsonData['inventory_count']['inventory_count']);
    }

    public function test_buy_returns_error_when_inventory_is_full(): void
    {
        $this->character->update(['gold' => 1000, 'inventory_max' => 0]);
        $item = $this->createItem(['cost' => 100]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/shop/buy/item/'.$this->character->id, ['item_id' => $item->id]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Inventory is full. Please make room.']);
    }

    public function test_buy_applies_merchant_discount_and_purchases_successfully(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Merchant'])->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000]);
        $item = $this->createItem(['cost' => 100, 'type' => 'shield']);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/shop/buy/item/'.$character->id, ['item_id' => $item->id]);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertStringStartsWith('Purchased:', $jsonData['message']);
    }

    public function test_shop_compare_returns_comparison_data(): void
    {
        $item = $this->createItem(['type' => 'shield', 'name' => 'Compare Shield']);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/shop/view/comparison/'.$this->character->id, [
                'item_name' => $item->name,
                'item_type' => $item->type,
            ]);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertArrayHasKey('comparison_data', $data);
        $this->assertArrayHasKey('details', $data['comparison_data']);
    }

    public function test_buy_and_replace_returns_generic_error_when_replacement_is_invalid(): void
    {
        $existingUniquePrefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true]);
        $existingUniqueItem = $this->createItem(['type' => 'shield', 'item_prefix_id' => $existingUniquePrefix->id]);
        $existingLeftHandShield = $this->createItem(['type' => 'shield']);

        $newUniquePrefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true]);
        $newUniqueShield = $this->createItem(['type' => 'shield', 'item_prefix_id' => $newUniquePrefix->id, 'cost' => 100]);

        $character = (new InventoryManagement($this->character))
            ->giveItem($existingUniqueItem, true, 'right-hand')
            ->giveItem($existingLeftHandShield, true, 'left-hand')
            ->getCharacter();

        $equippedSlot = $character->inventory->slots->firstWhere('item_id', $existingLeftHandShield->id);

        $character->update(['gold' => 50000]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/shop/buy-and-replace/'.$character->id, [
                'item_id_to_buy' => $newUniqueShield->id,
                'position' => 'left-hand',
                'slot_id' => $equippedSlot->id,
                'equip_type' => 'shield',
            ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Could not complete purchase.']);
        $response->assertJsonMissing(['message' => 'Cannot equip another unique.']);

        $character = $character->refresh();

        $this->assertSame(50000, $character->gold);
        $this->assertNull($character->inventory->slots->firstWhere('item_id', $newUniqueShield->id));
    }

    public function test_purchase_multiple_negative_amount_is_rejected(): void
    {
        $item = $this->createItem(['cost' => 100]);

        $response = $this->actingAs($this->character->user)->call('POST', '/api/shop/purchase/multiple/'.$this->character->id, [
            'item_id' => $item->id,
            'amount' => -1,
        ]);

        $response->assertStatus(302);
    }

    public function test_purchase_multiple_zero_amount_is_rejected(): void
    {
        $item = $this->createItem(['cost' => 100]);

        $response = $this->actingAs($this->character->user)->call('POST', '/api/shop/purchase/multiple/'.$this->character->id, [
            'item_id' => $item->id,
            'amount' => 0,
        ]);

        $response->assertStatus(302);
    }

    public function test_purchase_multiple_character_gold_is_unchanged_after_rejected_negative_amount(): void
    {
        $this->character->update(['gold' => 1000]);
        $item = $this->createItem(['cost' => 100]);

        $this->actingAs($this->character->user)->call('POST', '/api/shop/purchase/multiple/'.$this->character->id, [
            'item_id' => $item->id,
            'amount' => -2,
        ]);

        $this->assertSame(1000, $this->character->refresh()->gold);
    }

    public function test_purchase_multiple_valid_positive_amount_purchases_multiple_items(): void
    {
        Event::fake();

        $this->character->update(['gold' => 1000]);
        $item = $this->createItem(['cost' => 100]);

        $response = $this->actingAs($this->character->user)->call('POST', '/api/shop/purchase/multiple/'.$this->character->id, [
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertSame(800, $this->character->refresh()->gold);
        $this->assertSame(2, $this->character->inventory->slots()->where('item_id', $item->id)->count());
        $this->assertSame(800, $jsonData['gold']);
        $this->assertSame(2, $jsonData['inventory_count']['inventory_count']);
        $this->assertSame($this->character->inventory_max, $jsonData['inventory_count']['inventory_max']);
    }

    public function test_purchase_multiple_returns_error_when_amount_exceeds_inventory_space(): void
    {
        $this->character->update(['gold' => 100000, 'inventory_max' => 1]);
        $item = $this->createItem(['cost' => 100]);

        $response = $this->actingAs($this->character->user)->call('POST', '/api/shop/purchase/multiple/'.$this->character->id, [
            'item_id' => $item->id,
            'amount' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'You cannot purchase more then you have inventory space.']);
    }

    public function test_purchase_multiple_returns_error_when_not_enough_gold(): void
    {
        $this->character->update(['gold' => 10, 'inventory_max' => 200]);
        $item = $this->createItem(['cost' => 100]);

        $response = $this->actingAs($this->character->user)->call('POST', '/api/shop/purchase/multiple/'.$this->character->id, [
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'You do not have enough gold.']);
    }

    public function test_purchase_multiple_applies_merchant_discount_and_purchases_successfully(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Merchant'])->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $item = $this->createItem(['cost' => 100]);

        $response = $this->actingAs($character->user)->call('POST', '/api/shop/purchase/multiple/'.$character->id, [
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $response->assertStatus(200);
    }

    public function test_buy_and_replace_returns_error_when_item_is_craft_only(): void
    {
        $item = $this->createItem(['type' => 'shield', 'craft_only' => true, 'cost' => 100]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/shop/buy-and-replace/'.$this->character->id, [
                'item_id_to_buy' => $item->id,
                'position' => 'left-hand',
                'slot_id' => 1,
                'equip_type' => 'shield',
            ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'You are not capable of affording such luxury, child!']);
    }

    public function test_buy_and_replace_returns_error_when_not_enough_gold(): void
    {
        $existingShield = $this->createItem(['type' => 'shield']);
        $newShield = $this->createItem(['type' => 'shield', 'cost' => 1000]);

        $character = (new InventoryManagement($this->character))
            ->giveItem($existingShield, true, 'left-hand')
            ->getCharacter();

        $equippedSlot = $character->inventory->slots->firstWhere('item_id', $existingShield->id);

        $character->update(['gold' => 10]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/shop/buy-and-replace/'.$character->id, [
                'item_id_to_buy' => $newShield->id,
                'position' => 'left-hand',
                'slot_id' => $equippedSlot->id,
                'equip_type' => 'shield',
            ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'You do not have enough gold.']);
    }

    public function test_buy_and_replace_returns_error_when_inventory_is_full(): void
    {
        $existingShield = $this->createItem(['type' => 'shield']);
        $newShield = $this->createItem(['type' => 'shield', 'cost' => 100]);

        $character = (new InventoryManagement($this->character))
            ->giveItem($existingShield, true, 'left-hand')
            ->getCharacter();

        $equippedSlot = $character->inventory->slots->firstWhere('item_id', $existingShield->id);

        $character->update(['gold' => 100000, 'inventory_max' => 0]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/shop/buy-and-replace/'.$character->id, [
                'item_id_to_buy' => $newShield->id,
                'position' => 'left-hand',
                'slot_id' => $equippedSlot->id,
                'equip_type' => 'shield',
            ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Inventory is full. Please make room.']);
    }

    public function test_buy_and_replace_successfully_purchases_and_equips_replacement_item(): void
    {
        $existingShield = $this->createItem(['type' => 'shield']);
        $newShield = $this->createItem(['type' => 'shield', 'cost' => 100]);

        $character = (new InventoryManagement($this->character))
            ->giveItem($existingShield, true, 'left-hand')
            ->getCharacter();

        $equippedSlot = $character->inventory->slots->firstWhere('item_id', $existingShield->id);

        $character->update(['gold' => 100000, 'inventory_max' => 30]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/shop/buy-and-replace/'.$character->id, [
                'item_id_to_buy' => $newShield->id,
                'position' => 'left-hand',
                'slot_id' => $equippedSlot->id,
                'equip_type' => 'shield',
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertStringStartsWith('Purchased and equipped:', $jsonData['message']);
        $this->assertSame(99900, $jsonData['gold']);
        $this->assertArrayHasKey('inventory_max', $jsonData['inventory_count']);
        $this->assertArrayHasKey('inventory_count', $jsonData['inventory_count']);

        $character = $character->refresh();

        $this->assertSame(99900, $character->gold);
        $this->assertNotNull($character->inventory->slots->first(function ($slot) use ($newShield) {
            return $slot->item_id === $newShield->id && $slot->equipped;
        }));
    }

    public function test_buy_and_replace_applies_merchant_discount_and_purchases_successfully(): void
    {
        $existingShield = $this->createItem(['type' => 'shield']);
        $newShield = $this->createItem(['type' => 'shield', 'cost' => 100]);

        $character = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Merchant'])->givePlayerLocation()->getCharacter();
        $character = (new InventoryManagement($character))
            ->giveItem($existingShield, true, 'left-hand')
            ->getCharacter();

        $equippedSlot = $character->inventory->slots->firstWhere('item_id', $existingShield->id);

        $character->update(['gold' => 100000, 'inventory_max' => 30]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/shop/buy-and-replace/'.$character->id, [
                'item_id_to_buy' => $newShield->id,
                'position' => 'left-hand',
                'slot_id' => $equippedSlot->id,
                'equip_type' => 'shield',
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertStringStartsWith('Purchased and equipped:', $jsonData['message']);
    }
}
