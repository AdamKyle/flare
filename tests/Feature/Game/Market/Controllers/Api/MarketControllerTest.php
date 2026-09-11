<?php

namespace Tests\Feature\Game\Market\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Market\MarketScenarioFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class MarketControllerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function test_market_items_requires_authentication(): void
    {
        $response = $this->call('GET', '/api/market-board/items', ['item_id' => 1]);

        $response->assertStatus(302);
    }

    public function test_market_items_returns_unlocked_listings_for_requested_item_scoped_to_current_character_gold(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 500]);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($buyer->user)->call('GET', '/api/market-board/items', [
            'item_id' => $listing->item_id,
        ]);

        $response->assertOk();

        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data['items']['data']);
        $this->assertSame($listing->id, $data['items']['data'][0]['id']);
        $this->assertSame(500, $data['gold']);
    }

    public function test_market_items_excludes_locked_listings_for_the_requested_item(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();
        $listing->update(['is_locked' => true]);

        $response = $this->actingAs($buyer->user)->call('GET', '/api/market-board/items', [
            'item_id' => $listing->item_id,
        ]);

        $response->assertOk();

        $data = json_decode($response->getContent(), true);

        $this->assertCount(0, $data['items']['data']);
    }

    public function test_sell_item_rejects_a_non_market_sellable_item_without_mutating_money_or_inventory(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 0]);
        $character = $character->refresh();

        $scrollItem = $this->createGemXpScrollItem(0.10);
        $slot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $scrollItem->id,
        ]);

        $response = $this->actingAs($character->user)->call('POST', '/api/market-board/sell-item/'.$character->id, [
            'list_for' => 100,
            'slot_id' => $slot->id,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('inventory_slots', ['id' => $slot->id]);
        $this->assertDatabaseMissing('market_board', ['item_id' => $scrollItem->id]);
        $this->assertSame(0, $character->fresh()->gold);
    }
}
