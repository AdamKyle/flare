<?php

namespace Tests\Feature\Game\SpecialtyShops\Controllers\Api;

use App\Game\Character\Values\CharacterClass;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class SpecialtyShopControllerTest extends TestCase
{
    use CreateClass, CreateItem, CreateItemAffix, RefreshDatabase;

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

    public function test_fetch_items_returns_items_for_the_requested_specialty_type(): void
    {
        $character = $this->character->getCharacter();
        $this->createItem([
            'type' => 'sword',
            'specialty_type' => ItemSpecialtyType::HELL_FORGED->value,
            'cost' => 500,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/specialty-shop/'.$character->id.'?type='.urlencode(ItemSpecialtyType::HELL_FORGED->value));

        $response->assertOk();
        $this->assertCount(1, $response->json('items'));
    }

    public function test_fetch_items_returns_422_when_no_items_exist_for_the_type(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/specialty-shop/'.$character->id.'?type='.urlencode(ItemSpecialtyType::HELL_FORGED->value));

        $response->assertStatus(422);
        $this->assertSame('no items found for this shop.', $response->json('message'));
    }

    public function test_fetch_items_discounts_costs_by_five_percent_for_merchants(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::MERCHANT->value]))
            ->givePlayerLocation()
            ->getCharacter();
        $this->createItem([
            'type' => 'sword',
            'specialty_type' => ItemSpecialtyType::HELL_FORGED->value,
            'cost' => 100,
            'gold_dust_cost' => 200,
            'shards_cost' => 40,
            'copper_coin_cost' => 10,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/specialty-shop/'.$character->id.'?type='.urlencode(ItemSpecialtyType::HELL_FORGED->value));

        $response->assertOk();
        $item = $response->json('items')[0];
        $this->assertSame(95, $item['cost']);
        $this->assertSame(190, $item['gold_dust_cost']);
        $this->assertSame(38, $item['shards_cost']);
        $this->assertSame(9, $item['copper_coin_cost']);
    }

    public function test_purchase_item_returns_error_when_item_does_not_exist_for_the_type(): void
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem(['type' => 'sword', 'specialty_type' => ItemSpecialtyType::HELL_FORGED->value]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/specialty-shop/purchase/'.$character->id, [
                'item_id' => $item->id,
                'type' => ItemSpecialtyType::PURGATORY_CHAINS->value,
            ]);

        $response->assertStatus(422);
        $this->assertSame('Item is not found.', $response->json('message'));
    }

    public function test_purchase_item_returns_error_when_missing_trade_in_item(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['gold' => 1000]);
        $item = $this->createItem(['type' => 'sword', 'specialty_type' => ItemSpecialtyType::HELL_FORGED->value, 'cost' => 100]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/specialty-shop/purchase/'.$character->id, [
                'item_id' => $item->id,
                'type' => ItemSpecialtyType::HELL_FORGED->value,
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('You are missing an item of type: sword', $response->json('message'));
    }

    public function test_purchase_item_returns_error_when_character_cannot_afford_it(): void
    {
        $tradeItem = $this->createItem(['type' => 'sword', 'skill_level_trivial' => 400]);
        $character = $this->character->inventoryManagement()->giveItem($tradeItem)->getCharacter();
        $character->update(['gold' => 0]);
        $item = $this->createItem(['type' => 'sword', 'specialty_type' => ItemSpecialtyType::HELL_FORGED->value, 'cost' => 500]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/specialty-shop/purchase/'.$character->id, [
                'item_id' => $item->id,
                'type' => ItemSpecialtyType::HELL_FORGED->value,
            ]);

        $response->assertStatus(422);
        $this->assertSame('You do not have the currencies to purchase this.', $response->json('message'));
    }

    public function test_purchase_item_succeeds_and_trades_in_the_owned_item(): void
    {
        $tradeItem = $this->createItem(['type' => 'sword', 'skill_level_trivial' => 400]);
        $character = $this->character->inventoryManagement()->giveItem($tradeItem)->getCharacter();
        $character->update(['gold' => 1000]);
        $tradeSlotId = $character->inventory->slots()->where('item_id', $tradeItem->id)->first()->id;
        $item = $this->createItem(['type' => 'sword', 'specialty_type' => ItemSpecialtyType::HELL_FORGED->value, 'cost' => 100]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/specialty-shop/purchase/'.$character->id, [
                'item_id' => $item->id,
                'type' => ItemSpecialtyType::HELL_FORGED->value,
            ]);

        $response->assertOk();
        $this->assertSame(900, $character->fresh()->gold);
        $this->assertSame(0, $character->inventory->slots()->where('id', $tradeSlotId)->count());
        $this->assertSame(1, $character->inventory->slots()->where('item_id', $item->id)->count());
    }

    public function test_purchase_item_duplicates_the_item_when_trade_in_has_affixes(): void
    {
        $prefix = $this->createItemAffix();
        $tradeItem = $this->createItem(['type' => 'sword', 'skill_level_trivial' => 400, 'item_prefix_id' => $prefix->id]);
        $character = $this->character->inventoryManagement()->giveItem($tradeItem)->getCharacter();
        $character->update(['gold' => 1000]);
        $item = $this->createItem(['type' => 'sword', 'specialty_type' => ItemSpecialtyType::HELL_FORGED->value, 'cost' => 100]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/specialty-shop/purchase/'.$character->id, [
                'item_id' => $item->id,
                'type' => ItemSpecialtyType::HELL_FORGED->value,
            ]);

        $response->assertOk();
        $boughtSlot = $character->inventory->slots()->where('item_id', '!=', $item->id)->first();
        $this->assertNotNull($boughtSlot);
        $this->assertSame($prefix->id, $boughtSlot->item->item_prefix_id);
    }

    public function test_purchase_item_of_twisted_earth_type_requires_a_purgatory_chains_trade_in(): void
    {
        $tradeItem = $this->createItem(['type' => 'sword', 'specialty_type' => ItemSpecialtyType::PURGATORY_CHAINS->value]);
        $character = $this->character->inventoryManagement()->giveItem($tradeItem)->getCharacter();
        $character->update(['gold' => 1000]);
        $tradeSlotId = $character->inventory->slots()->where('item_id', $tradeItem->id)->first()->id;
        $item = $this->createItem(['type' => 'sword', 'specialty_type' => ItemSpecialtyType::TWISTED_EARTH->value, 'cost' => 100]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/specialty-shop/purchase/'.$character->id, [
                'item_id' => $item->id,
                'type' => ItemSpecialtyType::TWISTED_EARTH->value,
            ]);

        $response->assertOk();
        $this->assertSame(0, $character->inventory->slots()->where('id', $tradeSlotId)->count());
        $this->assertSame(1, $character->inventory->slots()->where('item_id', $item->id)->count());
    }
}
