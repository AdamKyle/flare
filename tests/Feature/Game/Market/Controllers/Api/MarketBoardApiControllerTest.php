<?php

namespace Tests\Feature\Game\Market\Controllers\Api;

use App\Flare\Models\GameMap;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\MarketBoard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMarketBoardListing;
use Tests\Traits\CreateMarketHistory;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class MarketBoardApiControllerTest extends TestCase {

    use RefreshDatabase,
        CreateLocation,
        CreateMarketHistory,
        CreateMarketBoardListing,
        CreateItem,
        CreateItemAffix,
        CreateUser,
        CreateRole;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCannotAccessCharacterItemsWhenNotAtAnyLocation() {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/character-items/' . $character->id)->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('You must first travel to a port to access the market board. Ports are blue ship icons on the map.', $content->error);
    }

    public function testCannotAccessCharacterItemsWhenNotAtMarketPort() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => false,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $character = $this->character->getCharacter();

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/character-items/' . $character->id)->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('You must first travel to a port to access the market board. Ports are blue ship icons on the map.', $content->error);
    }

    public function testCanAccessCharacterItems() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/character-items/' . $character->id)->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertCount(1, $content->slots);
    }

    public function testCanFetchCharacterItemsForType() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/character-items/' . $character->id, [
            'type' => 'weapon',
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertCount(1, $content->slots);
    }

    public function testCanFetchItemData() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/item', [
            'item_id' => $item->id,
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals($item->crafting_type, $content->crafting_type);
    }

    public function testCannotFetchItemData() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/item', [
            'item_id' => 3000,
        ])->response;

        $this->assertEquals(404, $response->status());
    }

    public function testCanFetchMarketHistory() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $this->createMarketHistory([
            'item_id' => $item->id,
            'sold_for' => 200,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/history')->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertCount(1, $content->labels);
        $this->assertCount(1, $content->data);
    }

    public function testCanFetchMarketHistoryWhenAdmin() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $this->createMarketHistory([
            'item_id' => $item->id,
            'sold_for' => 200,
        ]);

        $response = $this->actingAs($this->createAdmin($this->createAdminRole(), []))->json('GET', '/api/market-board/history')->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertCount(1, $content->labels);
        $this->assertCount(1, $content->data);
    }

    public function testCanFetchMarketHistoryForType() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $this->createMarketHistory([
            'item_id' => $item->id,
            'sold_for' => 200,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/history', [
            'type' => $item->crafting_type,
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertCount(1, $content->labels);
        $this->assertCount(1, $content->data);
    }

    public function testCanFetchMarketHistoryForToday() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $this->createMarketHistory([
            'item_id' => $item->id,
            'sold_for' => 200,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/history', [
            'when' => 'today'
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertCount(1, $content->labels);
        $this->assertCount(1, $content->data);
    }

    public function testCanFetchMarketHistoryForYesterday() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $this->createMarketHistory([
            'item_id' => $item->id,
            'sold_for' => 200,
        ]);

        DB::table('market_history')->update([
            'created_at' => Carbon::yesterday()
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/history', [
            'when' => 'last 24 hours'
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertCount(1, $content->labels);
        $this->assertCount(1, $content->data);
    }

    public function testCanFetchMarketHistoryForOneWeek() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $this->createMarketHistory([
            'item_id' => $item->id,
            'sold_for' => 200,
        ]);

        DB::table('market_history')->update([
            'created_at' => Carbon::today()->subWeek()
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/history', [
            'when' => '1 week'
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertCount(1, $content->labels);
        $this->assertCount(1, $content->data);
    }

    public function testCanFetchMarketHistoryForOneMonth() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $this->createMarketHistory([
            'item_id' => $item->id,
            'sold_for' => 200,
        ]);

        DB::table('market_history')->update([
            'created_at' => Carbon::today()->subMonth()
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/history', [
            'when' => '1 month'
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertCount(1, $content->labels);
        $this->assertCount(1, $content->data);
    }

    public function testCanFetchMarketHistoryForWhenDoesntExistDefaultToToday() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $this->createMarketHistory([
            'item_id' => $item->id,
            'sold_for' => 200,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/history', [
            'when' => '1 year' // does not exist, should hit default and still use today.
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertCount(1, $content->labels);
        $this->assertCount(1, $content->data);
    }

    public function testCanGeMarketListings() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $this->character->getCharacter()->id,
            'listed_price' => 1000
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/items')->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertCount(1, $content->items);
        $this->assertEquals($this->character->getCharacter()->gold, $content->gold);
    }

    public function testCanGeMarketListingsForType() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $this->character->getCharacter()->id,
            'listed_price' => 1000
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/items', [
            'type' => $item->crafting_type,
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertCount(1, $content->items);
        $this->assertEquals($this->character->getCharacter()->gold, $content->gold);
    }

    public function testCanGeMarketListingsForItem() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $this->character->getCharacter()->id,
            'listed_price' => 1000
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/items', [
            'item_id' => $item->id,
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertCount(1, $content->items);
        $this->assertEquals($this->character->getCharacter()->gold, $content->gold);
    }

    public function testCanSeeListingDetails() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', '/api/market-board/'.$item->id.'/listing-details')->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals($item->crafting_type, $content->crafting_type);
    }

    public function testCanSellItem() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($this->character->getUser())->json('POST', '/api/market-board/sell-item/' . $character->id, [
            'list_for' => 2000,
            'slot_id'  => InventorySlot::first()->id,
        ])->response;

        $this->assertEquals(200, $response->status());

        $this->assertTrue(MarketBoard::all()->isNotEmpty());
    }

    public function testCannotSellItemThatDoesNotExist() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $character = $this->character->getCharacter();

        $response = $this->actingAs($this->character->getUser())->json('POST', '/api/market-board/sell-item/' . $character->id, [
            'list_for' => 2000,
            'slot_id'  => 600,
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('item is not found.', $content->message);

        $this->assertTrue(MarketBoard::all()->isEmpty());
    }

    public function testCanPurchaseItem() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $marketBoardListing = $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $character->id,
            'listed_price' => 1
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', '/api/market-board/purchase/' . $this->character->getCharacter()->id, [
            'market_board_id' => $marketBoardListing->id,
        ])->response;

        $this->assertEquals(200, $response->status());
    }

    public function testCannotPurchaseItemThatDoesntExist() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', '/api/market-board/purchase/' . $this->character->getCharacter()->id, [
            'market_board_id' => 999,
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('Invalid Input.', $content->message);
    }

    public function testCanPurchaseItemInventoryFull() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $marketBoardListing = $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $character->id,
            'listed_price' => 1
        ]);

        $purchasingCharacter = $this->character->getCharacter();

        $purchasingCharacter->update([
            'inventory_max' => 0
        ]);

        $purchasingCharacter = $purchasingCharacter->refresh();

        $response = $this->actingAs($this->character->getUser())->json('POST', '/api/market-board/purchase/' . $purchasingCharacter->id, [
            'market_board_id' => $marketBoardListing->id,
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('Inventory is full.', $content->message);
    }

    public function testCanPurchaseItemNotEnoughGold() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $item = $this->createItem([
            'item_suffix_id'  => $this->createItemAffix(['type' => 'suffix']),
            'market_sellable' => true,
            'type'            => 'weapon',
        ]);

        $marketBoardListing = $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $character->id,
            'listed_price' => 10
        ]);

        $purchasingCharacter = $this->character->getCharacter();

        $purchasingCharacter->update([
            'gold' => 0
        ]);

        $purchasingCharacter = $purchasingCharacter->refresh();

        $response = $this->actingAs($this->character->getUser())->json('POST', '/api/market-board/purchase/' . $purchasingCharacter->id, [
            'market_board_id' => $marketBoardListing->id,
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('You don\'t have the gold to purchase this item.', $content->message);
    }
}
