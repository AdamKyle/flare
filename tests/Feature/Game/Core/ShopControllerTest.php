<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;

class ShopControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateUser;

    private $character;

    private $item;

    public function setUp(): void {
        parent::setUp();

        $this->item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => 6,
            'cost'        => 10,
        ]);
        

        $this->character = (new CharacterSetup())
                                ->setupCharacter($this->createUser())
                                ->giveItem($this->item)
                                ->equipLeftHand()
                                ->getCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;

        $this->item = null;
    }

    public function testCanSeeShop() {
        $this->actingAs($this->character->user)->visitRoute('game.shop.buy')->see('Weapons')->see('Rings');
    }

    public function testCanBuyItem() {
        $response = $this->actingAs($this->character->user)->post(route('game.shop.buy.item'), [
            'item_id' => $this->item->id,
        ])->response;

        $response->assertSessionHas('success', 'Purchased: ' . $this->item->name . '.');
    }

    public function testCannotBuyUnknownItem() {
        $response = $this->actingAs($this->character->user)->post(route('game.shop.buy.item'), [
            'item_id' => 6,
        ])->response;

        $response->assertSessionHas('error', 'Item not found.');
    }

    public function testCannotBuyItemNotEnoughGold() {
        $this->character->update([
            'gold' => 0,
        ]);

        $response = $this->actingAs($this->character->user)->post(route('game.shop.buy.item'), [
            'item_id' => 6,
        ])->response;

        $response->assertSessionHas('error', 'You do not have enough gold.');
    }

    public function testCannotBuyExpensiveItem() {
        $this->item->update([
            'cost' => 100,
        ]);

        $this->item->refresh();

        $response = $this->actingAs($this->character->user)->post(route('game.shop.buy.item'), [
            'item_id' => $this->item->id,
        ])->response;

        $response->assertSessionHas('error', 'You do not have enough gold.');
    }

    public function testCanSeeSellPage() {
        $this->actingAs($this->character->user)->visitRoute('game.shop.sell')->see('Inventory');
    }

    public function testCanSellItem() {
        $this->character->update([
            'gold' => 0,
        ]);

        $slot = $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $this->item->id,
            'equipped'     => false,
            'position'     => null,
        ]);

        $response = $this->actingAs($this->character->user)->post(route('game.shop.sell.item'), [
            'slot_id' => $slot->id,
        ])->response;

        $response->assertSessionHas('success', 'Sold: Rusty Dagger.');

        $this->character->refresh();

        $this->assertTrue($this->character->gold > 0);
    }

    public function testCanSellItemWithArtifactAndAfixes() {
        $this->character->update([
            'gold' => 0,
        ]);

        $this->item->artifactProperty()->create([
            'item_id'          => $this->item->id,
            'name'             => 'Sample',
            'base_damage_mod'  => 6,
            'description'      => 'sample',
        ]);

        $this->item->itemAffixes()->create([
            'item_id'          => $this->item->id,
            'name'             => 'Sample',
            'base_damage_mod'  => 6,
            'description'      => 'sample',
            'type'             => 'suffix',
        ]);

        $slot = $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $this->item->id,
            'equipped'     => false,
            'position'     => null,
        ]);

        $response = $this->actingAs($this->character->user)->post(route('game.shop.sell.item'), [
            'slot_id' => $slot->id,
        ])->response;

        $response->assertSessionHas('success', 'Sold: Rusty Dagger.');

        $this->character->refresh();

        $this->assertTrue($this->character->gold > 0);
    }


    public function testCannotSellIteYouDontHave() {
        $this->character->update([
            'gold' => 0,
        ]);

        $response = $this->actingAs($this->character->user)->post(route('game.shop.sell.item'), [
            'slot_id' => '10',
        ])->response;

        $response->assertSessionHas('error', 'Item not found.');

        $this->character->refresh();

        $this->assertFalse($this->character->gold > 0);
    }
}
