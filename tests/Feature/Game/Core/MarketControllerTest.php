<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\MarketBoard;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\traits\CreateLocation;
use Tests\traits\CreateAdventure;
use Tests\traits\CreateItem;


class MarketControllerTest extends TestCase
{
    use RefreshDatabase, CreateLocation, CreateAdventure, CreateItem;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();

        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 16,
            'y'           => 16,
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanVisitMarket() {

        $user = $this->character->givePlayerLocation()->getUser();

        $this->actingAs($user)->visitRoute('game.market')->see('market');
    }

    public function testCannotVisitMarketWhenNotAtPort() {

        $user = $this->character->givePlayerLocation(28, 48)->getUser();

        $this->actingAs($user)->visitRoute('game.market')->see('You must first travel to a port to access the market board. Ports are blue ship icons on the map.');
    }

    public function testCannotVisitMarketWhenAtLocationThatIsNotAPort() {

        $location = $this->createLocation([
            'name' => 'Adventure Location',
            'description' => 'not a port',
            'is_port' => false,
            'x' => 100,
            'y' => 70,
        ]);

        $user = $this->character->givePlayerLocation($location->x, $location->y)->getUser();

        $this->actingAs($user)->visitRoute('game.market')->see('You must first travel to a port to access the market board. Ports are blue ship icons on the map.');
    }

    public function testCannotVisitWhenDead() {

        $user = $this->character->updateCharacter(['is_dead' => true])->getUser();

        $this->actingAs($user)->visitRoute('game.market')->see('You are dead and must revive before trying to do that. Dead people can\'t do things.');
    }

    public function testCannotVisitWhenAdventuring() {

        $user = $this->character->createAdventureLog($this->createNewAdventure())->getUser();

        $this->actingAs($user)->visitRoute('game.market')->see('You are adventuring, you cannot do that.');
    }

    public function testCanSeeSellPage() {
        $user = $this->character->givePlayerLocation()->getUser();

        $this->actingAs($user)->visitRoute('game.market.sell')->see('Sell items on market board');
    }

    public function testCanListItem() {
        $character = $this->character->givePlayerLocation()
                                     ->inventoryManagement()
                                     ->giveItem($this->createitem())
                                     ->getCharacterFactory()
                                     ->getCharacter();
        
        $this->actingAs($character->user)->post(route('game.market.list', [
            'slot' => $character->inventory->slots->first()->id,
        ]), [
            'sell_for' => 100
        ])->followRedirects();

        $this->assertTrue($character->refresh()->inventory->slots->isEmpty());

        $this->assertTrue(MarketBoard::all()->isNotEmpty());
    }

    public function testCannotListItemBelowZero() {
        $character = $this->character->givePlayerLocation()
                                     ->inventoryManagement()
                                     ->giveItem($this->createitem())
                                     ->getCharacterFactory()
                                     ->getCharacter();
        
        $this->actingAs($character->user)->post(route('game.market.list', [
            'slot' => $character->inventory->slots->first()->id,
        ]), [
            'sell_for' => -100
        ]);

        $this->assertFalse($character->refresh()->inventory->slots->isEmpty());

        $this->assertFalse(MarketBoard::all()->isNotEmpty());
    }

    public function testCannotListItemWhenMissingSellFor() {
        $character = $this->character->givePlayerLocation()
                                     ->inventoryManagement()
                                     ->giveItem($this->createitem())
                                     ->getCharacterFactory()
                                     ->getCharacter();
        
        $this->actingAs($character->user)->post(route('game.market.list', [
            'slot' => $character->inventory->slots->first()->id,
        ]))->followRedirects();

        $this->assertFalse($character->refresh()->inventory->slots->isEmpty());

        $this->assertFalse(MarketBoard::all()->isNotEmpty());
    }

    public function testCanSeeCurrentListings() {
        $character = $this->character->givePlayerLocation()
                                     ->inventoryManagement()
                                     ->giveItem($this->createitem())
                                     ->getCharacterFactory()
                                     ->getCharacter();

        $this->actingAs($character->user)->visit(route('game.current-listings', [
            'character' => $character->id,
        ]))->see('This table is not live and may not reflect the markets.');
    }

    public function testCanCurrentListingBecomesUnLocked() {
        $character = $this->character->givePlayerLocation()
                                     ->getCharacter();

        $marketListing = MarketBoard::factory()->create([
            'character_id' => $character->id,
            'item_id'      => $this->createItem()->id,
            'listed_price' => 1000,
            'is_locked'    => true,
        ]);

        $this->actingAs($character->user)->visit(route('game.current-listings', [
            'character' => $character->id,
        ]))->see('This table is not live and may not reflect the markets.');

        $this->assertFalse($marketListing->refresh()->is_locked);
    }

    public function testCannotSeeAnotherCharactersListings() {
        $character = $this->character->givePlayerLocation()
                                     ->inventoryManagement()
                                     ->giveItem($this->createitem())
                                     ->getCharacterFactory()
                                     ->getCharacter();
        
        $secondCharacter = (new CharacterFactory)->createBaseCharacter();

        $this->actingAs($character->user)->visit(route('game.current-listings', [
            'character' => $secondCharacter->getCharacter()->id,
        ]))->see('You are not allowed to do that.');
    }

    public function testCanEditListedItem() {
        $character = $this->character->givePlayerLocation()
                                     ->getCharacter();

        $marketListing = MarketBoard::factory()->create([
            'character_id' => $character->id,
            'item_id'      => $this->createItem()->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $this->actingAs($character->user)->visit('game')->visit(route('game.edit.current-listings', [
            'marketBoard' => $marketListing->id,
        ]))->see($marketListing->item->name);

        $this->assertTrue($marketListing->refresh()->is_locked);
    }

    public function testCannotEditAnotherCharactersListedItem() {
        $character = $this->character->givePlayerLocation()
                                     ->getCharacter();

        $marketListing = MarketBoard::factory()->create([
            'character_id' => (new CharacterFactory)->createBaseCharacter()->getCharacter()->id,
            'item_id'      => $this->createItem()->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $this->actingAs($character->user)->visit('game')->visit(route('game.edit.current-listings', [
            'marketBoard' => $marketListing->id,
        ]))->see('You are not allowed to do that.');

        $this->assertFalse($marketListing->refresh()->is_locked);
    }

    public function testCanUpdateCurrentListing() {
        $character = $this->character->givePlayerLocation()
                                     ->getCharacter();

        $marketListing = MarketBoard::factory()->create([
            'character_id' => $character->id,
            'item_id'      => $this->createItem()->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $this->actingAs($character->user)->visit('game')->visit(route('game.edit.current-listings', [
            'marketBoard' => $marketListing->id,
        ]))->see($marketListing->item->name)
           ->submitForm('Update Pricing', [
               'listed_price' => 2000,
           ])
           ->see('Listing for: ' . $marketListing->item->affix_name . ' updated.');

        $this->assertEquals(2000, $marketListing->refresh()->listed_price);
        $this->assertFalse($marketListing->refresh()->is_locked);
    }

    public function testCannotUpdateCurrentListingWhenPriceBelowZero() {
        $character = $this->character->givePlayerLocation()
                                     ->getCharacter();

        $marketListing = MarketBoard::factory()->create([
            'character_id' => $character->id,
            'item_id'      => $this->createItem()->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $this->actingAs($character->user)->visit('game')->visit(route('game.edit.current-listings', [
            'marketBoard' => $marketListing->id,
        ]))->see($marketListing->item->name)
           ->submitForm('Update Pricing', [
               'listed_price' => -100,
           ])
           ->see('Listed price cannot be below or equal to 0.');

        $this->assertEquals(1000, $marketListing->refresh()->listed_price);
        $this->assertTrue($marketListing->refresh()->is_locked);
    }

    public function testCanDelistItem() {
        $character = $this->character->givePlayerLocation()
                                     ->getCharacter();

        $marketListing = MarketBoard::factory()->create([
            'character_id' => $character->id,
            'item_id'      => $this->createItem()->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $this->actingAs($character->user)->post(route('game.delist.current-listing', [
            'marketBoard' => $marketListing->id
        ]));

        $this->assertTrue($character->refresh()->inventory->slots->isNotEmpty());
    }

    public function testCannotDelistSomeOneElsesItem() {
        $character = $this->character->givePlayerLocation()
                                     ->getCharacter();

        $secondCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $marketListing = MarketBoard::factory()->create([
            'character_id' => $secondCharacter->id,
            'item_id'      => $this->createItem()->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $this->actingAs($character->user)->post(route('game.delist.current-listing', [
            'marketBoard' => $marketListing->id
        ]));

        $this->assertFalse($secondCharacter->refresh()->refresh()->inventory->slots->isNotEmpty());
    }

    public function testCannotDelistItemInventoryFull() {
        $character = $this->character->givePlayerLocation()
                                     ->updateCharacter(['inventory_max' => 0])
                                     ->getCharacter();

        $marketListing = MarketBoard::factory()->create([
            'character_id' => $character->id,
            'item_id'      => $this->createItem()->id,
            'listed_price' => 1000,
            'is_locked'    => false,
        ]);

        $this->actingAs($character->user)->post(route('game.delist.current-listing', [
            'marketBoard' => $marketListing->id
        ]));

        $this->assertFalse($character->refresh()->inventory->slots->isNotEmpty());
    }
}
