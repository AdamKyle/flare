<?php

namespace Tests\Feature\Game\Market\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Market\MarketScenarioFactory;
use Tests\TestCase;

class MarketControllerTest extends TestCase
{
    use RefreshDatabase;

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
}
