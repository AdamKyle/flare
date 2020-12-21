<?php

namespace Tests\Feature\Game\Core\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\traits\CreateLocation;
use Tests\traits\CreateAdventure;
use Tests\traits\CreateItem;


class MarketControllerApiTest extends TestCase
{
    use RefreshDatabase, CreateLocation, CreateAdventure, CreateItem;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 16,
            'y'           => 16,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetMarketListings() {

        $user     = $this->character->getUser();

        MarketBoard::factory()->create([
            'character_id' => $user->character->id,
            'item_id'      => $this->createItem(['type' => 'feet'])->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $response = $this->actingAs($user, 'api')->json('GET', '/api/market-board/items')->response;

        $this->assertEquals(200, $response->status());
        
        $content = json_decode($response->content());

        $this->assertNotEmpty($content->items);
        $this->assertEquals(10, $content->gold);
    }

    public function testGetMarketListingsForType() {

        $user     = $this->character->getUser();

        MarketBoard::factory()->create([
            'character_id' => $user->character->id,
            'item_id'      => $this->createItem(['type' => 'feet'])->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $response = $this->actingAs($user, 'api')->json('GET', '/api/market-board/items', [
            'type' => 'feet'
        ])->response;

        $this->assertEquals(200, $response->status());
        
        $content = json_decode($response->content());

        $this->assertNotEmpty($content->items);
        $this->assertEquals(10, $content->gold);
    }
    
    public function testGetMarketListingsForItemId() {

        $user     = $this->character->getUser();

        MarketBoard::factory()->create([
            'character_id' => $user->character->id,
            'item_id'      => $this->createItem(['type' => 'feet'])->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $response = $this->actingAs($user, 'api')->json('GET', '/api/market-board/items', [
            'item_id' => 1,
        ])->response;

        $this->assertEquals(200, $response->status());
        
        $content = json_decode($response->content());

        $this->assertNotEmpty($content->items);
        $this->assertEquals(10, $content->gold);
    }

    public function testGetNoItemsWhenMarketListingLocked() {

        $user     = $this->character->getUser();

        MarketBoard::factory()->create([
            'character_id' => $user->character->id,
            'item_id'      => $this->createItem(['type' => 'feet'])->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $response = $this->actingAs($user, 'api')->json('GET', '/api/market-board/items', [
            'item_id' => 1,
        ])->response;

        $this->assertEquals(200, $response->status());
        
        $content = json_decode($response->content());

        $this->assertEmpty($content->items);
        $this->assertEquals(10, $content->gold);
    }

    public function testCannotGetMarketListingNotLoggedIn() {

        $user     = $this->character->getUser();

        MarketBoard::factory()->create([
            'character_id' => $user->character->id,
            'item_id'      => $this->createItem(['type' => 'feet'])->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $response = $this->json('GET', '/api/market-board/items')->response;

        $this->assertEquals(401, $response->status());
        
        $content = json_decode($response->content());

        $this->assertEquals('Unauthenticated.', $content->message);
    }

    public function testCannotGetMarketListingsWhenDead() {

        $user     = $this->character->updateCharacter(['is_dead' => true])->getUser();

        MarketBoard::factory()->create([
            'character_id' => $user->character->id,
            'item_id'      => $this->createItem(['type' => 'feet'])->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $response = $this->actingAs($user, 'api')->json('GET', '/api/market-board/items')->response;

        $this->assertEquals(422, $response->status());
        
        $content = json_decode($response->content());

        $this->assertEquals("You are dead and must revive before trying to do that. Dead people can't do things.", $content->error);
    }

    public function testCannotGetMarketListingsWhenAdventuring() {

        $user     = $this->character->createAdventureLog($this->createNewAdventure())->getUser();

        MarketBoard::factory()->create([
            'character_id' => $user->character->id,
            'item_id'      => $this->createItem(['type' => 'feet'])->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $response = $this->actingAs($user, 'api')->json('GET', '/api/market-board/items')->response;

        $this->assertEquals(422, $response->status());
        
        $content = json_decode($response->content());

        $this->assertEquals("You are adventuring, you cannot do that.", $content->error);
    }

    public function testCannotGetMarketListingsWhenNotatLocation() {

        $user     = $this->character->updateLocation(28, 79)->getUser();

        MarketBoard::factory()->create([
            'character_id' => $user->character->id,
            'item_id'      => $this->createItem(['type' => 'feet'])->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $response = $this->actingAs($user, 'api')->json('GET', '/api/market-board/items')->response;

        $this->assertEquals(422, $response->status());
        
        $content = json_decode($response->content());

        $this->assertEquals("You must first travel to a port to access the market board. Ports are blue ship icons on the map.", $content->error);
    }

    public function testCannotGetMarketListingsWhenLocationIsNotPort() {

        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => false,
            'x'           => 26,
            'y'           => 26,
        ]);

        $user     = $this->character->updateLocation(26, 26)->getUser();

        MarketBoard::factory()->create([
            'character_id' => $user->character->id,
            'item_id'      => $this->createItem(['type' => 'feet'])->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $response = $this->actingAs($user, 'api')->json('GET', '/api/market-board/items')->response;

        $this->assertEquals(422, $response->status());
        
        $content = json_decode($response->content());

        $this->assertEquals("You must first travel to a port to access the market board. Ports are blue ship icons on the map.", $content->error);
    }

    public function testFetchMarketItemDetails() {

        $user     = $this->character->getUser();

        MarketBoard::factory()->create([
            'character_id' => $user->character->id,
            'item_id'      => $this->createItem(['type' => 'feet'])->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $response = $this->actingAs($user, 'api')->json('GET', '/api/market-board/1/listing-details')->response;

        $this->assertEquals(200, $response->status());
        
        $content = json_decode($response->content());

        $this->assertEquals(1, $content->id);
        $this->assertEquals('feet', $content->type);
    }

    public function testPurchaseItemFromMarketBoard() {

        $user     = $this->character->updateCharacter(['gold' => 1500])->getUser();

        MarketBoard::factory()->create([
            'character_id' => $user->character->id,
            'item_id'      => $this->createItem(['type' => 'feet'])->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $response = $this->actingAs($user, 'api')->json('POST', '/api/market-board/purchase/1', [
            'market_board_id' => 1
        ])->response;

        $this->assertEquals(200, $response->status());

        $character = $user->character->refresh();

        $this->assertTrue($character->inventory->slots->isNotEmpty());
        $this->assertNotEquals(1500, $character->gold);
    }

    public function testFailToPurchaseItemFromMarketBoardWhenDoesntExist() {

        $user     = $this->character->updateCharacter(['gold', 1500])->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', '/api/market-board/purchase/1', [
            'market_board_id' => 1
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('Invalid Input.', $content->message);
    }

    public function testFailToPurchaseItemFromMarketBoardWhenInventoryFull() {

        $user     = $this->character->updateCharacter(['inventory_max' => 0])->getUser();

        MarketBoard::factory()->create([
            'character_id' => $user->character->id,
            'item_id'      => $this->createItem(['type' => 'feet'])->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $response = $this->actingAs($user, 'api')->json('POST', '/api/market-board/purchase/1', [
            'market_board_id' => 1
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('Inventory is full.', $content->message);
    }

    public function testFailToPurchaseItemFromMarketBoardWhenDontHaveTheGold() {

        $user     = $this->character->updateCharacter(['gold' => 0])->getUser();

        MarketBoard::factory()->create([
            'character_id' => $user->character->id,
            'item_id'      => $this->createItem(['type' => 'feet'])->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $response = $this->actingAs($user, 'api')->json('POST', '/api/market-board/purchase/1', [
            'market_board_id' => 1
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals("You don't have the gold to puchase this item.", $content->message);
    }

    public function testGetMarketHistory() {

        $user     = $this->character->getUser();

        MarketHistory::factory()->create([
            'item_id'  => $this->createItem(['type' => 'feet'])->id,
            'sold_for' => 1000,
        ]);

        $response = $this->actingAs($user, 'api')->json('GET', '/api/market-board/history')->response;

        $this->assertEquals(200, $response->status());

        $content = json_decode($response->content());

        $this->assertNotEmpty($content->labels);
        $this->assertNotEmpty($content->data);
    }

    public function testGetMarketHistoryForType() {

        $user     = $this->character->getUser();

        MarketHistory::factory()->create([
            'item_id'  => $this->createItem(['type' => 'feet'])->id,
            'sold_for' => 1000,
        ]);

        $response = $this->actingAs($user, 'api')->json('GET', '/api/market-board/history', [
            'type' => 'feet',
        ])->response;

        $this->assertEquals(200, $response->status());

        $content = json_decode($response->content());

        $this->assertNotEmpty($content->labels);
        $this->assertNotEmpty($content->data);
    }
}
