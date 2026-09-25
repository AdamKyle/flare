<?php

namespace Tests\Feature\Game\Market\Controllers\Api;

use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Events\UpdateMarketBoardBroadcastEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Market\MarketScenarioFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateMarketBoardListing;

class MarketControllerTest extends TestCase
{
    use CreateBatchCrafting, CreateItem, CreateItemAffix, CreateMarketBoardListing, RefreshDatabase;

    public function test_market_items_requires_authentication(): void
    {
        $response = $this->call('GET', '/api/market-board/items');

        $response->assertStatus(302);
    }

    public function test_market_api_is_denied_when_character_is_not_at_a_port(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($character->user)->getJson('/api/market-board/items');

        $response->assertStatus(422);
        $response->assertJson(['message' => 'You must first travel to a port to access the market board. Ports are blue ship icons on the map.']);
    }

    public function test_market_access_is_granted_when_the_character_stands_on_a_port(): void
    {
        $character = (new MarketScenarioFactory)->createScenario()->buyer()->getCharacter();

        $response = $this->actingAs($character->user)->getJson('/api/market-board/access/'.$character->id);

        $response->assertOk();
        $response->assertExactJson(['can_access_market' => true]);
    }

    public function test_market_access_is_refused_when_the_character_is_not_on_a_port(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($character->user)->getJson('/api/market-board/access/'.$character->id);

        $response->assertOk();
        $response->assertExactJson(['can_access_market' => false]);
    }

    public function test_browse_returns_unlocked_listings_with_canonical_item_details(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($buyer->user)->getJson('/api/market-board/items?per_page=10&page=1');

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertCount(1, $data['data']);
        $this->assertSame($listing->id, $data['data'][0]['id']);
        $this->assertSame($listing->item_id, $data['data'][0]['item']['id']);
        $this->assertFalse($data['meta']['can_load_more']);
    }

    public function test_browse_excludes_locked_listings(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $buyer = $scenario->buyer()->getCharacter();
        $scenario->listing()->update(['is_locked' => true]);

        $response = $this->actingAs($buyer->user)->getJson('/api/market-board/items?per_page=10&page=1');

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertCount(0, $data['data']);
    }

    public function test_browse_only_returns_listings_of_the_requested_item_type(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario([], ['type' => 'shield']);

        $buyer = $scenario->buyer()->getCharacter();
        $shieldListing = $scenario->listing();

        $this->createMarketBoardListing([
            'character_id' => $scenario->seller()->getCharacterId(),
            'item_id' => $this->createItem(['type' => 'ring'])->id,
            'listed_price' => 100,
        ]);

        $response = $this->actingAs($buyer->user)->getJson('/api/market-board/items?per_page=10&page=1&filters[type]=shield');

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertCount(1, $data['data']);
        $this->assertSame($shieldListing->id, $data['data'][0]['id']);
    }

    public function test_browse_only_returns_listings_whose_item_name_matches_the_search_text(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario([], ['name' => 'Sword of Dawn']);

        $buyer = $scenario->buyer()->getCharacter();
        $matchingListing = $scenario->listing();

        $this->createMarketBoardListing([
            'character_id' => $scenario->seller()->getCharacterId(),
            'item_id' => $this->createItem(['name' => 'Plain Helmet'])->id,
            'listed_price' => 100,
        ]);

        $response = $this->actingAs($buyer->user)->getJson('/api/market-board/items?per_page=10&page=1&search_text=Dawn');

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertCount(1, $data['data']);
        $this->assertSame($matchingListing->id, $data['data'][0]['id']);
    }

    public function test_browse_orders_listings_by_the_requested_price_direction(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario([], [], 500);

        $buyer = $scenario->buyer()->getCharacter();
        $expensiveListing = $scenario->listing();

        $cheapListing = $this->createMarketBoardListing([
            'character_id' => $scenario->seller()->getCharacterId(),
            'item_id' => $this->createItem()->id,
            'listed_price' => 50,
        ]);

        $response = $this->actingAs($buyer->user)->getJson('/api/market-board/items?per_page=10&page=1&filters[sort_price]=asc');

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame([$cheapListing->id, $expensiveListing->id], array_column($data['data'], 'id'));
    }

    public function test_listing_details_refuses_a_locked_listing_to_a_non_owner(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();
        $listing->update(['is_locked' => true]);

        $response = $this->actingAs($buyer->user)->getJson('/api/market-board/items/'.$listing->id);

        $response->assertStatus(422);
    }

    public function test_listing_details_returns_an_available_listing(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($buyer->user)->getJson('/api/market-board/items/'.$listing->id);

        $response->assertOk();
        $response->assertJsonPath('listing.id', $listing->id);
    }

    public function test_owned_listings_only_returns_the_authenticated_characters_listings(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $buyer = $scenario->buyer()->getCharacter();
        $buyerListing = $this->createMarketBoardListing([
            'character_id' => $buyer->id,
            'item_id' => $this->createItem()->id,
            'listed_price' => 500,
        ]);

        $response = $this->actingAs($buyer->user)->getJson('/api/market-board/current-listings/'.$buyer->id.'?per_page=10&page=1');

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertCount(1, $data['data']);
        $this->assertSame($buyerListing->id, $data['data'][0]['id']);
    }

    public function test_compare_rejects_the_characters_own_listing(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $seller = $scenario->seller()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($seller->user)->postJson('/api/market-board/items/'.$listing->id.'/compare/'.$seller->id);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'You cannot compare your own listing.']);
    }

    public function test_compare_returns_comparison_and_pricing_for_another_characters_listing(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario([], ['type' => 'shield'], 1000);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/compare/'.$buyer->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertArrayHasKey('details', $data);
        $this->assertSame($listing->item_id, $data['item_to_equip']['item_id']);
        $this->assertSame($listing->id, $data['market_board_id']);
        $this->assertSame(1000, $data['listed_price']);
        $this->assertSame(1050, $data['total_price']);
    }

    public function test_buy_rejects_the_characters_own_listing(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $seller = $scenario->seller()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($seller->user)->postJson('/api/market-board/items/'.$listing->id.'/buy/'.$seller->id);

        $response->assertStatus(422);
        $this->assertTrue(MarketBoard::whereKey($listing->id)->exists());
    }

    public function test_buy_rejects_a_locked_listing(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 5000, 'inventory_max' => 75]);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();
        $listing->update(['is_locked' => true]);

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy/'.$buyer->id);

        $response->assertStatus(422);
        $this->assertSame(5000, $buyer->refresh()->gold);
    }

    public function test_atomic_reservation_prevents_acquiring_a_listing_reserved_after_it_was_read(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 5000, 'inventory_max' => 75]);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        MarketBoard::retrieved(function (MarketBoard $retrievedListing) {
            MarketBoard::whereKey($retrievedListing->id)->update(['is_locked' => true]);
        });

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy/'.$buyer->id);

        $response->assertStatus(422);
        $this->assertSame(5000, $buyer->refresh()->gold);
        $this->assertSame(0, MarketHistory::count());
    }

    public function test_inventory_full_purchase_failure_unlocks_the_listing(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 5000, 'inventory_max' => 0]);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy/'.$buyer->id);

        $response->assertStatus(422);
        $this->assertFalse($listing->refresh()->is_locked);
    }

    public function test_insufficient_gold_purchase_failure_unlocks_the_listing(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 0, 'inventory_max' => 75]);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy/'.$buyer->id);

        $response->assertStatus(422);
        $this->assertFalse($listing->refresh()->is_locked);
    }

    public function test_failed_purchase_publishes_the_restored_market(): void
    {
        Event::fake([UpdateMarketBoardBroadcastEvent::class]);

        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 0, 'inventory_max' => 75]);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy/'.$buyer->id);

        Event::assertDispatched(UpdateMarketBoardBroadcastEvent::class, function (UpdateMarketBoardBroadcastEvent $event) use ($listing) {
            return collect($event->marketListings['data'])->contains('id', $listing->id);
        });
    }

    public function test_successful_buy_deletes_the_listing_and_transfers_the_item(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 5000, 'inventory_max' => 75]);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy/'.$buyer->id);

        $response->assertOk();
        $this->assertFalse(MarketBoard::whereKey($listing->id)->exists());
        $this->assertTrue($buyer->refresh()->inventory->slots()->where('item_id', $listing->item_id)->exists());
    }

    public function test_successful_buy_returns_the_buyers_gold_and_inventory_count(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 5000, 'inventory_max' => 75]);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy/'.$buyer->id);

        $response->assertOk();
        $response->assertJsonPath('gold', 4895);
        $response->assertJsonStructure(['message', 'gold', 'inventory_count' => ['inventory_count', 'inventory_max']]);
    }

    public function test_buyer_is_charged_the_listed_price_plus_five_percent(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 5000, 'inventory_max' => 75], [], 1000);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy/'.$buyer->id);

        $this->assertSame(3950, $buyer->refresh()->gold);
    }

    public function test_buyer_total_price_is_capped_at_maximum_gold(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => CurrencyLimit::MAX_GOLD, 'inventory_max' => 75], [], CurrencyLimit::MAX_GOLD);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy/'.$buyer->id);

        $response->assertOk();
        $this->assertSame(0, $buyer->refresh()->gold);
    }

    public function test_seller_receives_the_listed_price_minus_five_percent(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 5000, 'inventory_max' => 75], [], 1000);

        $buyer = $scenario->buyer()->getCharacter();
        $seller = $scenario->seller()->updateCharacter(['gold' => 0])->getCharacter();
        $listing = $scenario->listing();

        $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy/'.$buyer->id);

        $this->assertSame(950, $seller->refresh()->gold);
    }

    public function test_seller_gold_is_capped_at_maximum_gold(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 5000, 'inventory_max' => 75], [], 1000);

        $buyer = $scenario->buyer()->getCharacter();
        $seller = $scenario->seller()->updateCharacter(['gold' => CurrencyLimit::MAX_GOLD - 100])->getCharacter();
        $listing = $scenario->listing();

        $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy/'.$buyer->id);

        $this->assertSame(CurrencyLimit::MAX_GOLD, $seller->refresh()->gold);
    }

    public function test_market_history_records_the_listed_price(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 5000, 'inventory_max' => 75], [], 1000);

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy/'.$buyer->id);

        $this->assertSame(1000, MarketHistory::where('item_id', $listing->item_id)->first()->sold_for);
    }

    public function test_invalid_buy_and_replace_eligibility_unlocks_the_listing(): void
    {
        $existingUniquePrefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true]);
        $listedUniquePrefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true]);

        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 5000, 'inventory_max' => 75], [
            'type' => 'shield',
            'item_prefix_id' => $listedUniquePrefix->id,
        ]);

        $buyer = $scenario->buyer()->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'shield', 'item_prefix_id' => $existingUniquePrefix->id]), true, 'right-hand')
            ->giveItem($this->createItem(['type' => 'shield']), true, 'left-hand')
            ->getCharacter();

        $listing = $scenario->listing();
        $replacedSlot = $buyer->inventory->slots()->where('position', 'left-hand')->first();

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy-and-replace/'.$buyer->id, [
            'position' => 'left-hand',
            'slot_id' => $replacedSlot->id,
            'equip_type' => 'shield',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Cannot equip another unique.']);
        $this->assertFalse($listing->refresh()->is_locked);
    }

    public function test_successful_buy_and_replace_equips_the_item_and_deletes_the_listing(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 5000, 'inventory_max' => 75], ['type' => 'shield']);

        $buyer = $scenario->buyer()->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'shield']), true, 'left-hand')
            ->getCharacter();

        $listing = $scenario->listing();
        $replacedSlot = $buyer->inventory->slots()->where('position', 'left-hand')->first();

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy-and-replace/'.$buyer->id, [
            'position' => 'left-hand',
            'slot_id' => $replacedSlot->id,
            'equip_type' => 'shield',
        ]);

        $response->assertOk();
        $this->assertFalse(MarketBoard::whereKey($listing->id)->exists());
        $this->assertTrue($buyer->refresh()->inventory->slots()->where('item_id', $listing->item_id)->where('equipped', true)->exists());
    }

    public function test_buy_and_replace_accepts_the_trinket_equipment_position(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 5000, 'inventory_max' => 75], ['type' => 'trinket']);

        $buyer = $scenario->buyer()->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'trinket']), true, 'trinket')
            ->getCharacter();

        $listing = $scenario->listing();
        $replacedSlot = $buyer->inventory->slots()->where('position', 'trinket')->first();

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy-and-replace/'.$buyer->id, [
            'position' => 'trinket',
            'slot_id' => $replacedSlot->id,
            'equip_type' => 'trinket',
        ]);

        $response->assertOk();
        $this->assertTrue($buyer->refresh()->inventory->slots()->where('item_id', $listing->item_id)->where('equipped', true)->exists());
    }

    public function test_buy_and_replace_rejects_an_unknown_equipment_position(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 5000, 'inventory_max' => 75], ['type' => 'shield']);

        $buyer = $scenario->buyer()->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'shield']), true, 'left-hand')
            ->getCharacter();

        $listing = $scenario->listing();
        $replacedSlot = $buyer->inventory->slots()->where('position', 'left-hand')->first();

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy-and-replace/'.$buyer->id, [
            'position' => 'shield',
            'slot_id' => $replacedSlot->id,
            'equip_type' => 'shield',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['position' => 'Select a valid equipment position.']);
        $this->assertTrue(MarketBoard::whereKey($listing->id)->exists());
    }

    public function test_buy_and_replace_rejects_an_item_type_that_cannot_be_equipped(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario(['gold' => 5000, 'inventory_max' => 75], ['type' => 'shield']);

        $buyer = $scenario->buyer()->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'shield']), true, 'left-hand')
            ->getCharacter();

        $listing = $scenario->listing();
        $replacedSlot = $buyer->inventory->slots()->where('position', 'left-hand')->first();

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/items/'.$listing->id.'/buy-and-replace/'.$buyer->id, [
            'position' => 'left-hand',
            'slot_id' => $replacedSlot->id,
            'equip_type' => 'quest',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['equip_type' => 'The item type to equip is not valid.']);
        $this->assertTrue(MarketBoard::whereKey($listing->id)->exists());
    }

    public function test_begin_edit_rejects_a_non_owner(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/current-listings/'.$listing->id.'/edit/'.$buyer->id);

        $response->assertStatus(422);
        $this->assertFalse($listing->refresh()->is_locked);
    }

    public function test_begin_edit_locks_the_listing(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $seller = $scenario->seller()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($seller->user)->postJson('/api/market-board/current-listings/'.$listing->id.'/edit/'.$seller->id);

        $response->assertOk();
        $this->assertTrue($listing->refresh()->is_locked);
    }

    public function test_begin_edit_rejects_a_listing_that_is_already_locked(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $seller = $scenario->seller()->getCharacter();
        $listing = $scenario->listing();
        $listing->update(['is_locked' => true]);

        $response = $this->actingAs($seller->user)->postJson('/api/market-board/current-listings/'.$listing->id.'/edit/'.$seller->id);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'This listing is already locked. It may be in the middle of a sale or another edit.']);
    }

    public function test_begin_edit_publishes_the_market_without_the_locked_listing(): void
    {
        Event::fake([UpdateMarketBoardBroadcastEvent::class]);

        $scenario = (new MarketScenarioFactory)->createScenario();

        $seller = $scenario->seller()->getCharacter();
        $listing = $scenario->listing();

        $this->actingAs($seller->user)->postJson('/api/market-board/current-listings/'.$listing->id.'/edit/'.$seller->id);

        Event::assertDispatched(UpdateMarketBoardBroadcastEvent::class, function (UpdateMarketBoardBroadcastEvent $event) use ($listing) {
            return ! collect($event->marketListings['data'])->contains('id', $listing->id);
        });
    }

    public function test_update_rejects_a_non_owner(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($buyer->user)->patchJson('/api/market-board/current-listings/'.$listing->id.'/'.$buyer->id, [
            'listed_price' => 5000,
        ]);

        $response->assertStatus(422);
        $this->assertSame(100, $listing->refresh()->listed_price);
    }

    public function test_update_rejects_a_non_positive_price(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $seller = $scenario->seller()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($seller->user)->patchJson('/api/market-board/current-listings/'.$listing->id.'/'.$seller->id, [
            'listed_price' => 0,
        ]);

        $response->assertStatus(422);
        $this->assertSame(100, $listing->refresh()->listed_price);
    }

    public function test_update_caps_the_price_at_maximum_gold(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $seller = $scenario->seller()->getCharacter();
        $listing = $scenario->listing();

        $this->actingAs($seller->user)->patchJson('/api/market-board/current-listings/'.$listing->id.'/'.$seller->id, [
            'listed_price' => CurrencyLimit::MAX_GOLD + 1000,
        ]);

        $this->assertSame(CurrencyLimit::MAX_GOLD, $listing->refresh()->listed_price);
    }

    public function test_update_unlocks_the_listing_after_saving(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $seller = $scenario->seller()->getCharacter();
        $listing = $scenario->listing();
        $listing->update(['is_locked' => true]);

        $response = $this->actingAs($seller->user)->patchJson('/api/market-board/current-listings/'.$listing->id.'/'.$seller->id, [
            'listed_price' => 2500,
        ]);

        $response->assertOk();
        $this->assertFalse($listing->refresh()->is_locked);
        $this->assertSame(2500, $listing->listed_price);
    }

    public function test_cancel_edit_unlocks_without_changing_the_price(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $seller = $scenario->seller()->getCharacter();
        $listing = $scenario->listing();
        $listing->update(['is_locked' => true]);

        $response = $this->actingAs($seller->user)->postJson('/api/market-board/current-listings/'.$listing->id.'/cancel-edit/'.$seller->id);

        $response->assertOk();
        $this->assertFalse($listing->refresh()->is_locked);
        $this->assertSame(100, $listing->listed_price);
    }

    public function test_cancel_edit_rejects_a_non_owner_without_unlocking_the_listing(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();
        $listing->update(['is_locked' => true]);

        $response = $this->actingAs($buyer->user)->postJson('/api/market-board/current-listings/'.$listing->id.'/cancel-edit/'.$buyer->id);

        $response->assertStatus(422);
        $this->assertTrue($listing->refresh()->is_locked);
    }

    public function test_delist_is_blocked_while_batch_crafting_is_running(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $seller = $scenario->seller()->updateCharacter(['inventory_max' => 75])->getCharacter();
        $listing = $scenario->listing();

        $this->createBatchCrafting(['character_id' => $seller->id, 'user_id' => $seller->user_id]);

        $response = $this->actingAs($seller->user)->deleteJson('/api/market-board/current-listings/'.$listing->id.'/'.$seller->id);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'You cannot do that while Batch Crafting is running. Cancel it first.']);
        $this->assertTrue(MarketBoard::whereKey($listing->id)->exists());
    }

    public function test_delist_rejects_a_non_owner(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $buyer = $scenario->buyer()->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($buyer->user)->deleteJson('/api/market-board/current-listings/'.$listing->id.'/'.$buyer->id);

        $response->assertStatus(422);
        $this->assertTrue(MarketBoard::whereKey($listing->id)->exists());
    }

    public function test_delist_rejects_a_full_inventory_without_deleting_the_listing(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $seller = $scenario->seller()->updateCharacter(['inventory_max' => 0])->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($seller->user)->deleteJson('/api/market-board/current-listings/'.$listing->id.'/'.$seller->id);

        $response->assertStatus(422);
        $this->assertTrue(MarketBoard::whereKey($listing->id)->exists());
    }

    public function test_delist_returns_the_item_and_deletes_the_listing(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $seller = $scenario->seller()->updateCharacter(['inventory_max' => 75])->getCharacter();
        $listing = $scenario->listing();

        $response = $this->actingAs($seller->user)->deleteJson('/api/market-board/current-listings/'.$listing->id.'/'.$seller->id);

        $response->assertOk();
        $this->assertFalse(MarketBoard::whereKey($listing->id)->exists());
        $this->assertTrue($seller->refresh()->inventory->slots()->where('item_id', $listing->item_id)->exists());
    }

    public function test_sell_item_rejects_a_non_market_sellable_item_without_mutating_money_or_inventory(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $character = $scenario->buyer()->updateCharacter(['gold' => 0])->getCharacter();

        $scrollItem = $this->createGemXpScrollItem(0.10);
        $slot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $scrollItem->id,
        ]);

        $response = $this->actingAs($character->user)->postJson('/api/market-board/sell-item/'.$character->id, [
            'list_for' => 100,
            'slot_id' => $slot->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'This item cannot be sold on the Market.']);
        $this->assertDatabaseHas('inventory_slots', ['id' => $slot->id]);
        $this->assertDatabaseMissing('market_board', ['item_id' => $scrollItem->id]);
    }

    public function test_sell_item_is_blocked_while_batch_crafting_is_running(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $item = $this->createItem();
        $inventory = $scenario->buyer()->inventoryManagement()->giveItem($item);
        $character = $inventory->getCharacter();

        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);

        $response = $this->actingAs($character->user)->postJson('/api/market-board/sell-item/'.$character->id, [
            'list_for' => 1000,
            'slot_id' => $inventory->getSlotId(0),
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'You cannot do that while Batch Crafting is running. Cancel it first.']);
        $this->assertDatabaseMissing('market_board', ['item_id' => $item->id]);
    }

    public function test_sell_item_rejects_a_slot_that_is_not_in_the_characters_inventory(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $character = $scenario->buyer()->getCharacter();

        $response = $this->actingAs($character->user)->postJson('/api/market-board/sell-item/'.$character->id, [
            'list_for' => 1000,
            'slot_id' => 999999,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'The item you want to list could not be found in your inventory.']);
    }

    public function test_sell_item_rejects_a_price_below_the_minimum_selling_price(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $inventory = $scenario->buyer()->inventoryManagement()->giveItem($this->createItem([
            'type' => 'trinket',
            'gold_dust_cost' => 100,
        ]));
        $character = $inventory->getCharacter();

        $response = $this->actingAs($character->user)->postJson('/api/market-board/sell-item/'.$character->id, [
            'list_for' => 100,
            'slot_id' => $inventory->getSlotId(0),
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'No! The minimum selling price is: 10,000 Gold.']);
    }

    public function test_sell_item_rejects_a_non_positive_listing_price(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $item = $this->createItem();
        $inventory = $scenario->buyer()->inventoryManagement()->giveItem($item);
        $character = $inventory->getCharacter();

        $response = $this->actingAs($character->user)->postJson('/api/market-board/sell-item/'.$character->id, [
            'list_for' => 0,
            'slot_id' => $inventory->getSlotId(0),
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('market_board', ['item_id' => $item->id]);
    }

    public function test_sell_item_creates_a_listing_at_the_requested_price(): void
    {
        $scenario = (new MarketScenarioFactory)->createScenario();

        $item = $this->createItem(['cost' => 100]);
        $inventory = $scenario->buyer()->inventoryManagement()->giveItem($item);
        $character = $inventory->getCharacter();
        $slotId = $inventory->getSlotId(0);

        $response = $this->actingAs($character->user)->postJson('/api/market-board/sell-item/'.$character->id, [
            'list_for' => 1000,
            'slot_id' => $slotId,
        ]);

        $response->assertOk();
        $this->assertSame(1000, MarketBoard::where('item_id', $item->id)->first()->listed_price);
        $this->assertDatabaseMissing('inventory_slots', ['id' => $slotId]);
    }

    public function test_sell_item_publishes_the_new_listing(): void
    {
        Event::fake([UpdateMarketBoardBroadcastEvent::class]);

        $scenario = (new MarketScenarioFactory)->createScenario();

        $item = $this->createItem();
        $inventory = $scenario->buyer()->inventoryManagement()->giveItem($item);
        $character = $inventory->getCharacter();

        $this->actingAs($character->user)->postJson('/api/market-board/sell-item/'.$character->id, [
            'list_for' => 1000,
            'slot_id' => $inventory->getSlotId(0),
        ]);

        Event::assertDispatched(UpdateMarketBoardBroadcastEvent::class, function (UpdateMarketBoardBroadcastEvent $event) use ($item) {
            return collect($event->marketListings['data'])->contains('item_id', $item->id);
        });
    }
}
