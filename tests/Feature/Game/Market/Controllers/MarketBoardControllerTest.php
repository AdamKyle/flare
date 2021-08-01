<?php

namespace Tests\Feature\Game\Market\Controllers;

use App\Flare\Models\GameMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMarketBoardListing;
use Tests\Traits\CreateMarketHistory;

class MarketBoardControllerTest extends TestCase {

    use RefreshDatabase, CreateLocation, CreateMarketHistory, CreateMarketBoardListing, CreateItem;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCannotAccessMarketBoard() {
        $this->actingAs($this->character->getUser())->visitRoute('game.market')->see('You must first travel to a port to access the market board. Ports are blue ship icons on the map.');
    }

    public function testCannotAccessMarketBoardIsLocationButNotPort() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $this->actingAs($this->character->getUser())->visitRoute('game.market')->see('You must first travel to a port to access the market board. Ports are blue ship icons on the map.');
    }

    public function testCanSeeMarket() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $this->actingAs($this->character->getUser())->visitRoute('game.market')->see('You can click on the row in the table to open the modal to buy or browse.');
    }

    public function testCanSeeCurrentListings() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem();

        $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $this->character->getCharacter()->id,
            'listed_price' => 1000
        ]);

        $this->actingAs($this->character->getUser())->visitRoute('game.current-listings', [
            'character' => $this->character->getCharacter()->id,
        ])->see($item->name);
    }

    public function testCanSeeSellingPage() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $this->actingAs($this->character->getUser())->visitRoute('game.market.sell')->see('Sell items on market board');
    }

    public function testCanSeeCurrentListingsWhereItemIsLocked() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem();

        $marketBoardListing = $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $this->character->getCharacter()->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $this->actingAs($this->character->getUser())->visitRoute('game.current-listings', [
            'character' => $this->character->getCharacter()->id,
        ])->see($item->name);

        $this->assertFalse($marketBoardListing->refresh()->is_locked);
    }

    public function testCanEditCurrentListing() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem();

        $marketBoardListing = $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $this->character->getCharacter()->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $this->actingAs($this->character->getUser())->visitRoute('game.edit.current-listings', [
            'marketBoard' => $marketBoardListing->id,
        ])->see($item->name);
    }

    public function testCanEditListingReLocksListing() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem();

        $marketBoardListing = $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $this->character->getCharacter()->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $this->actingAs($this->character->getUser())->visitRoute('game.edit.current-listings', [
            'marketBoard' => $marketBoardListing->id,
        ])->see($item->name);

        $this->assertTrue($marketBoardListing->refresh()->is_locked);
    }

    public function testCannotEditListingOfOthers() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem();

        $marketBoardListing = $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $this->character->getCharacter()->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->actingAs($character->user)->visitRoute('game.market')->visitRoute('game.edit.current-listings', [
            'marketBoard' => $marketBoardListing->id,
        ])->dontSee($item->name)->see('You are not allowed to do that.');
    }

    public function testCanUpdateListing() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem();

        $marketBoardListing = $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $this->character->getCharacter()->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $response = $this->actingAs($this->character->getUser())->post(route('game.update.current-listing', [
            'marketBoard' => $marketBoardListing->id,
        ]), [
            'listed_price' => 2000
        ])->response;


        $this->assertEquals(2000, $marketBoardListing->refresh()->listed_price);
    }

    public function testCannotUpdateListing() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem();

        $marketBoardListing = $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $this->character->getCharacter()->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $response = $this->actingAs($this->character->getUser())->post(route('game.update.current-listing', [
            'marketBoard' => $marketBoardListing->id,
        ]), [
            'listed_price' => -2000
        ])->response;

        $response->assertSessionHas('error', 'Listed price cannot be below or equal to 0.');

        $this->assertEquals(1000, $marketBoardListing->refresh()->listed_price);
    }

    public function testCannotUpdateListingOfOthers() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem();

        $marketBoardListing = $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $this->character->getCharacter()->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($character->user)->post(route('game.update.current-listing', [
            'marketBoard' => $marketBoardListing->id,
        ]), [
            'listed_price' => 2000
        ])->response;

        $response->assertSessionHas('error', 'You are not allowed to do that.');

        $this->assertEquals(1000, $marketBoardListing->refresh()->listed_price);
    }

    public function testCanDelistItem() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem();

        $marketBoardListing = $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $this->character->getCharacter()->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $response = $this->actingAs($this->character->getUser())->post(route('game.delist.current-listing', [
            'marketBoard' => $marketBoardListing->id,
        ]))->response;

        $response->assertSessionHas('success', 'Delisted: ' . $item->name);
    }

    public function testCannotDelistItemInventoryFull() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem();

        $character = $this->character->getCharacter();

        $character->update([
            'inventory_max' => 0
        ]);

        $character = $character->refresh();

        $marketBoardListing = $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $character->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $response = $this->actingAs($character->user)->post(route('game.delist.current-listing', [
            'marketBoard' => $marketBoardListing->id,
        ]))->response;

        $response->assertSessionHas('error', 'You don\'t have the inventory space to delist the item.');
    }

    public function testCannotDelistOthersItem() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => GameMap::first()->id,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $item = $this->createItem();

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $marketBoardListing = $this->createMarketBoardListing([
            'item_id'      => $item->id,
            'character_id' => $this->character->getCharacter()->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $response = $this->actingAs($character->user)->post(route('game.delist.current-listing', [
            'marketBoard' => $marketBoardListing->id,
        ]))->response;

        $response->assertSessionHas('error', 'You are not allowed to do that.');
    }
}
