<?php

namespace Tests\Feature\Game\Market\Controllers;

use App\Flare\Models\InventorySet;
use App\Flare\Models\Location;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\SetSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class MarketControllerSecurityTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function test_failed_purchase_for_full_inventory_unlocks_listing(): void
    {
        [$buyer, $listing] = $this->marketScenario([
            'gold' => 1000,
            'inventory_max' => 0,
        ]);

        $this->actingAs($buyer->user)->post(route('game.market.buy', [
            'character' => $buyer->id,
        ]), [
            'market_board_id' => $listing->id,
        ]);

        $this->assertFalse($listing->refresh()->is_locked);
    }

    public function test_failed_purchase_for_low_gold_unlocks_listing(): void
    {
        [$buyer, $listing] = $this->marketScenario([
            'gold' => 0,
            'inventory_max' => 75,
        ]);

        $this->actingAs($buyer->user)->post(route('game.market.buy', [
            'character' => $buyer->id,
        ]), [
            'market_board_id' => $listing->id,
        ]);

        $this->assertFalse($listing->refresh()->is_locked);
    }

    public function test_failed_buy_and_replace_for_full_inventory_unlocks_listing(): void
    {
        [$buyer, $listing] = $this->marketScenario([
            'gold' => 1000,
            'inventory_max' => 0,
        ]);

        $this->actingAs($buyer->user)->post(route('game.market.buy-and-replace', [
            'character' => $buyer->id,
        ]), [
            'market_board_id' => $listing->id,
            'position' => 'left-hand',
        ]);

        $this->assertFalse($listing->refresh()->is_locked);
    }

    public function test_failed_buy_and_replace_for_low_gold_unlocks_listing(): void
    {
        [$buyer, $listing] = $this->marketScenario([
            'gold' => 0,
            'inventory_max' => 75,
        ]);

        $this->actingAs($buyer->user)->post(route('game.market.buy-and-replace', [
            'character' => $buyer->id,
        ]), [
            'market_board_id' => $listing->id,
            'position' => 'left-hand',
        ]);

        $this->assertFalse($listing->refresh()->is_locked);
    }

    public function test_failed_buy_and_replace_rolls_back_purchase_and_unlocks_listing(): void
    {
        [$buyer, $listing] = $this->marketScenario([
            'gold' => 1000,
            'inventory_max' => 75,
        ], [
            'type' => 'trinket',
        ]);
        $inventorySet = InventorySet::create([
            'character_id' => $buyer->id,
            'is_equipped' => true,
            'can_be_equipped' => true,
        ]);
        SetSlot::create([
            'inventory_set_id' => $inventorySet->id,
            'item_id' => $this->createItem(['type' => 'trinket'])->id,
            'equipped' => true,
            'position' => 'trinket',
        ]);
        $seller = $listing->character;
        $buyerGold = $buyer->gold;
        $sellerGold = $seller->gold;
        $inventoryCount = $buyer->inventory->slots()->count();
        $historyCount = MarketHistory::count();

        $response = $this->actingAs($buyer->user)->post(route('game.market.buy-and-replace', [
            'character' => $buyer->id,
        ]), [
            'market_board_id' => $listing->id,
            'position' => 'trinket',
        ])->response;

        $response->assertSessionHas('error');
        $this->assertFalse($listing->refresh()->is_locked);
        $this->assertSame($buyerGold, $buyer->refresh()->gold);
        $this->assertSame($sellerGold, $seller->refresh()->gold);
        $this->assertSame($inventoryCount, $buyer->inventory->slots()->count());
        $this->assertSame($historyCount, MarketHistory::count());
        $this->assertTrue(MarketBoard::where('id', $listing->id)->exists());
    }

    private function marketScenario(array $buyerAttributes, array $listingItemAttributes = []): array
    {
        $buyer = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->updateCharacter($buyerAttributes)
            ->getCharacter();
        $seller = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        Location::create([
            'name' => 'Market Port',
            'game_map_id' => $buyer->map->game_map_id,
            'description' => 'Port',
            'is_port' => true,
            'can_players_enter' => true,
            'can_auto_battle' => true,
            'x' => $buyer->x_position,
            'y' => $buyer->y_position,
        ]);
        $listing = MarketBoard::create([
            'character_id' => $seller->id,
            'item_id' => $this->createItem($listingItemAttributes)->id,
            'listed_price' => 100,
            'is_locked' => false,
        ]);

        return [$buyer, $listing];
    }
}
