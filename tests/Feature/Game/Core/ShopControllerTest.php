<?php

namespace Tests\Feature\Game\Core;

use App\Flare\Models\InventorySlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateItemAffix;

class ShopControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateUser,
        CreateItemAffix;

    private $character;

    private $item;

    private $itemAffix;

    public function setUp(): void {
        parent::setUp();

        $this->item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => 6,
            'cost'        => 10,
        ]);

        $this->itemAffix = $this->createItemAffix([
            'name'                 => 'Sample',
            'base_damage_mod'      => '0.10',
            'type'                 => 'suffix',
            'description'          => 'Sample',
            'base_healing_mod'     => '0.10',
            'str_mod'              => '0.10',
            'dur_mod'              => '0.10',
            'dex_mod'              => '0.10',
            'chr_mod'              => '0.10',
            'int_mod'              => '0.10',
            'skill_name'           => null,
            'skill_training_bonus' => null,
        ]);

        $this->character = (new CharacterFactory)
                                ->createBaseCharacter()
                                ->givePlayerLocation()
                                ->inventoryManagement()
                                ->giveItem($this->item)
                                ->equipLeftHand($this->item->name)
                                ->getCharacterFactory();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;

        $this->item = null;

        $this->itemAffix = null;
    }

    public function testCanSeeShop() {
        $user = $this->character->getUser();

        $this->actingAs($user)->visitRoute('game.shop.buy', ['character' => $this->character->getCharacter()->id])->see('Buying');
    }

    public function testCanBuyItem() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.buy.item', ['character' => $this->character->getCharacter()->id]), [
            'item_id' => $this->item->id,
        ])->response;

        $response->assertSessionHas('success', 'Purchased: ' . $this->item->name . '.');
    }

    public function testCannotBuyUnknownItem() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.buy.item', ['character' => $this->character->getCharacter()->id]), [
            'item_id' => 6,
        ])->response;

        $response->assertSessionHas('error', 'Item not found.');
    }

    public function testCannotBuyItemNotEnoughGold() {

        $user = $this->character->updateCharacter(['gold' => 0])->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.buy.item', ['character' => $this->character->getCharacter()->id]), [
            'item_id' => 6,
        ])->response;

        $response->assertSessionHas('error', 'You do not have enough gold.');
    }

    public function testCannotBuyExpensiveItem() {
        $this->item->update([
            'cost' => 100,
        ]);

        $this->item->refresh();

        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.buy.item', ['character' => $this->character->getCharacter()->id]), [
            'item_id' => $this->item->id,
        ])->response;

        $response->assertSessionHas('error', 'You do not have enough gold.');
    }

    public function testCanSeeSellPage() {
        $user = $this->character->getUser();

        $this->actingAs($user)->visitRoute('game.shop.sell', ['character' => $this->character->getCharacter()->id])->see('Inventory');
    }

    public function testCanSellItem() {

        $user = $this->character->inventoryManagement()
                                ->unequipAll()
                                ->getCharacterFactory()
                                ->updateCharacter([
                                    'gold' => 0
                                ])
                                ->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.sell.item', ['character' => $this->character->getCharacter()->id]), [
            'slot_id' => InventorySlot::first()->id,
        ])->response;

        $sellFor = SellItemCalculator::fetchTotalSalePrice($this->item);

        $response->assertSessionHas('success', 'Sold: Rusty Dagger for: '.$sellFor.' gold.');

        $this->assertTrue($this->character->getCharacter()->gold > 0);
    }


    public function testCannotSellIteYouDontHave() {
        $user = $this->character->updateCharacter([
                                    'gold' => 0
                                ])
                                ->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.sell.item', ['character' => $this->character->getCharacter()->id]), [
            'slot_id' => '10',
        ])->response;

        $response->assertSessionHas('error', 'Item not found.');

        $this->assertFalse($this->character->getCharacter()->gold > 0);
    }

    public function testCannotSellAllWhenAllEquipped() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.sell.all', ['character' => $this->character->getCharacter()->id]))->response;

        $response->assertSessionHas('error', 'You have nothing that you can sell.');
    }

    public function testSellAllItems() {
        $user = $this->character->inventoryManagement()
                                ->unequipAll()
                                ->getCharacterFactory()
                                ->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.sell.all', ['character' => $this->character->getCharacter()->id]))->response;

        $response->assertSessionHas('success', 'Sold all your unequipped items for a total of: 10 gold.');
    }

    public function testSellAllItemsButQuestItems() {
        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'quest',
            'base_damage' => 6,
            'cost'        => 10,
        ]);

        $weapon = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => 6,
            'cost'        => 10,
        ]);

        $user = $this->character->inventoryManagement()
                                ->giveItem($item)
                                ->giveItem($weapon)
                                ->getCharacterFactory()
                                ->getUser();


        $response = $this->actingAs($user)->post(route('game.shop.sell.all', ['character' => $this->character->getCharacter()->id]))->response;

        $response->assertSessionHas('success', 'Sold all your unequipped items for a total of: 10 gold.');

        $questItems = $this->character->getCharacter()->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'quest';
        })->all();

        $this->assertFalse(empty($questItems));
    }

    public function testCanBulkBuy() {

        $user = $this->character->updateCharacter(['gold' => 100000000])->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.buy.bulk', ['character' => $this->character->getCharacter()->id]),
        [
            'items' => [$this->item->id]
        ])->response;

        $response->assertSessionHas('success', 'Puchased all selected items.');
    }

    public function testFailToBulkBuyWhenNoGold() {

        $user = $this->character->updateCharacter(['gold' => 0])->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.buy.bulk', ['character' => $this->character->getCharacter()->id]), [
            'items' => [$this->item->id]
        ])->response;

        $response->assertSessionHas('error', 'You do not have enough gold.');
    }


    public function testFailToBulkBuyWhenNoItems() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.buy.bulk', ['character' => $this->character->getCharacter()->id]),[
            'items' => []
        ])->response;

        $response->assertSessionHas('error', 'No items could be found. Did you select any?');
    }

    public function testNotEnoughGold() {
        $user = $this->character->updateCharacter(['gold' => 10])->getUser();

        $weapon = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => 6,
            'cost'        => 10,
        ]);

        $response = $this->actingAs($user)->post(route('game.shop.buy.bulk', ['character' => $this->character->getCharacter()->id]), [
            'items' => [$this->item->id, $weapon->id]
        ])->response;

        $response->assertSessionHas('error', 'You do not have enough gold to buy: ' . $this->item->name . '. Anything before this item in the list was purchased.');

        $count = $this->character->getCharacter()->inventory->slots->count();

        $this->assertEquals($count, 2);
    }

    public function testBuyAllSelectedItems() {
        $user = $this->character->updateCharacter(['gold' => 10])->getUser();

        $weapon = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => 6,
            'cost'        => 10,
        ]);

        $response = $this->actingAs($user)->post(route('game.shop.buy.bulk', ['character' => $this->character->getCharacter()->id]), [
            'items' => [$this->item->id, $weapon->id]
        ])->response;

        $count = $this->character->getCharacter()->inventory->slots->count();

        $this->assertEquals($count, 2);
    }

    public function testFailToSellInBulk() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.sell.bulk', ['character' => $this->character->getCharacter()->id]), [
            'slots' => []
        ])->response;

        $response->assertSessionHas('error', 'No items could be found. Did you select any?');
    }

    public function testSellInBulk() {
        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'quest',
            'base_damage' => 6,
            'cost'        => 10,
        ]);

        $weapon = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => 6,
            'cost'        => 10,
        ]);

        $user      = $this->character->inventoryManagement()
                                ->giveItem($item)
                                ->giveItem($weapon)
                                ->getCharacterFactory()
                                ->getUser();
        $character = $this->character->getCharacter();


        $response = $this->actingAs($user)->post(route('game.shop.sell.bulk', ['character' => $this->character->getCharacter()->id]), [
            'slots' => $character->inventory->slots->filter(function($slot) {
                return !$slot->equipped && $slot->item->type !== 'quest';
            })->pluck('id')->toArray(),
        ])->response;

        $response->assertSessionHas('success', 'Sold selected items for: 10 gold.');
    }
}
