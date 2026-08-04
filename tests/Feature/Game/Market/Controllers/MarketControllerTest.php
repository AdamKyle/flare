<?php

namespace Tests\Feature\Game\Market\Controllers;

use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Market\MarketScenarioFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class MarketControllerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function test_buy_rejects_purchase_when_inventory_is_full(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario([
            'gold' => 1000,
            'inventory_max' => 0,
        ]);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();
        $buyerGoldBeforePurchase = $buyer->gold;
        $historyCountBeforePurchase = MarketHistory::count();

        $response = $this->actingAs($buyer->user)->call('POST', route('game.market.buy', [
            'character' => $buyer->id,
        ]), [
            'market_board_id' => $listing->id,
        ]);

        $response->assertRedirect(route('game.market'));
        $response->assertSessionHas('error');
        $this->assertFalse($listing->refresh()->is_locked);
        $this->assertSame($buyerGoldBeforePurchase, $buyer->refresh()->gold);
        $this->assertSame($historyCountBeforePurchase, MarketHistory::count());
    }

    public function test_buy_rejects_purchase_when_character_cannot_afford_item(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario([
            'gold' => 0,
            'inventory_max' => 75,
        ]);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();
        $buyerGoldBeforePurchase = $buyer->gold;
        $historyCountBeforePurchase = MarketHistory::count();

        $response = $this->actingAs($buyer->user)->post(route('game.market.buy', [
            'character' => $buyer->id,
        ]), [
            'market_board_id' => $listing->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertFalse($listing->refresh()->is_locked);
        $this->assertSame($buyerGoldBeforePurchase, $buyer->refresh()->gold);
        $this->assertSame($historyCountBeforePurchase, MarketHistory::count());
    }

    public function test_buy_and_replace_rejects_purchase_when_inventory_is_full(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario([
            'gold' => 1000,
            'inventory_max' => 0,
        ]);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();
        $buyerGoldBeforePurchase = $buyer->gold;
        $historyCountBeforePurchase = MarketHistory::count();

        $response = $this->actingAs($buyer->user)->call('POST', route('game.market.buy-and-replace', [
            'character' => $buyer->id,
        ]), [
            'market_board_id' => $listing->id,
            'position' => 'left-hand',
        ]);

        $response->assertRedirect(route('game.market'));
        $response->assertSessionHas('error');
        $this->assertFalse($listing->refresh()->is_locked);
        $this->assertSame($buyerGoldBeforePurchase, $buyer->refresh()->gold);
        $this->assertSame($historyCountBeforePurchase, MarketHistory::count());
    }

    public function test_buy_and_replace_rejects_purchase_when_character_cannot_afford_item(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario([
            'gold' => 0,
            'inventory_max' => 75,
        ]);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();
        $buyerGoldBeforePurchase = $buyer->gold;
        $historyCountBeforePurchase = MarketHistory::count();

        $response = $this->actingAs($buyer->user)->call('POST', route('game.market.buy-and-replace', [
            'character' => $buyer->id,
        ]), [
            'market_board_id' => $listing->id,
            'position' => 'left-hand',
        ]);

        $response->assertSessionHas('error');
        $this->assertFalse($listing->refresh()->is_locked);
        $this->assertSame($buyerGoldBeforePurchase, $buyer->refresh()->gold);
        $this->assertSame($historyCountBeforePurchase, MarketHistory::count());
    }

    public function test_buy_and_replace_rejects_invalid_replacement_without_changing_market_state(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario([
            'gold' => 1000,
            'inventory_max' => 75,
        ], [
            'type' => 'trinket',
        ]);

        $buyer = $scenario->buyer()
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($this->createItem(['type' => 'trinket']), 0, 'trinket', true)
            ->getCharacter();

        $seller = $scenario->seller()->getCharacter();
        $listing = $scenario->listing();

        $buyerGoldBeforePurchase = $buyer->gold;
        $sellerGoldBeforePurchase = $seller->gold;
        $buyerInventoryCountBeforePurchase = $buyer->inventory->slots()->count();
        $historyCountBeforePurchase = MarketHistory::count();

        $response = $this->actingAs($buyer->user)->call('POST', route('game.market.buy-and-replace', [
            'character' => $buyer->id,
        ]), [
            'market_board_id' => $listing->id,
            'position' => 'trinket',
        ]);

        $response->assertSessionHas('error');
        $this->assertFalse($listing->refresh()->is_locked);
        $this->assertSame($buyerGoldBeforePurchase, $buyer->refresh()->gold);
        $this->assertSame($sellerGoldBeforePurchase, $seller->refresh()->gold);
        $this->assertSame($buyerInventoryCountBeforePurchase, $buyer->inventory->slots()->count());
        $this->assertSame($historyCountBeforePurchase, MarketHistory::count());
        $this->assertTrue(MarketBoard::whereKey($listing->id)->exists());
    }

    public function test_sell_item_rejects_negative_listing_price(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $inventory = $scenario->seller()->inventoryManagement()->giveItem($this->createItem());
        $seller = $inventory->getCharacter();
        $slotId = $inventory->getSlotId(0);
        $marketListingCountBeforeSale = MarketBoard::count();

        $this->actingAs($seller->user)->json('POST', '/api/market-board/sell-item/'.$seller->id, [
            'slot_id' => $slotId,
            'list_for' => -1,
        ]);
        $response = $this->response;

        $response->assertStatus(422);
        $this->assertSame($marketListingCountBeforeSale, MarketBoard::count());
        $this->assertSame(1, $seller->inventory->slots()->count());
    }

    public function test_sell_item_rejects_zero_listing_price(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $inventory = $scenario->seller()->inventoryManagement()->giveItem($this->createItem());
        $seller = $inventory->getCharacter();
        $slotId = $inventory->getSlotId(0);
        $marketListingCountBeforeSale = MarketBoard::count();

        $this->actingAs($seller->user)->json('POST', '/api/market-board/sell-item/'.$seller->id, [
            'slot_id' => $slotId,
            'list_for' => 0,
        ]);
        $response = $this->response;

        $response->assertStatus(422);
        $this->assertSame($marketListingCountBeforeSale, MarketBoard::count());
        $this->assertSame(1, $seller->inventory->slots()->count());
    }

    public function test_sell_item_creates_listing_for_positive_price(): void
    {
        Event::fake();

        $scenario = (new MarketScenarioFactory)->createScenario();

        $item = $this->createItem(['cost' => 100]);
        $inventory = $scenario->seller()->inventoryManagement()->giveItem($item);
        $seller = $inventory->getCharacter();
        $slotId = $inventory->getSlotId(0);

        $response = $this->actingAs($seller->user)->call('POST', '/api/market-board/sell-item/'.$seller->id, [
            'slot_id' => $slotId,
            'list_for' => 1000,
        ]);

        $response->assertStatus(200);
        $newListing = MarketBoard::where('item_id', $item->id)->first();
        $this->assertNotNull($newListing);
        $this->assertSame(1000, $newListing->listed_price);
        $this->assertSame(0, $seller->inventory->slots()->count());
    }
}
