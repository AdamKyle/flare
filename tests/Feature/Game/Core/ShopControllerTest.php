<?php

namespace Tests\Feature\Game\Core;

use App\Game\Core\Jobs\PurchaseItemsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Flare\Models\InventorySlot;
use Illuminate\Support\Facades\Queue;
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
            'name'          => 'Rusty Dagger',
            'type'          => 'weapon',
            'base_damage'   => 6,
            'cost'          => 10,
            'crafting_type' => 'weapon',
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

        $this->actingAs($user)->visitRoute('game.shop.buy', ['character' => $this->character->getCharacter(false)->id])->see('Buying');
    }

    public function testCanBuyItem() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.buy.item', ['character' => $this->character->getCharacter(false)->id]), [
            'item_id' => $this->item->id,
        ])->response;

        $response->assertSessionHas('success', 'Purchased: ' . $this->item->name . '.');
    }

    public function testCanSeeComparePageForComparingShopItems() {
        $user = $this->character->getUser();

        $this->actingAs($user)->visitRoute('game.shop.compare.item', [
            'character' => $this->character->getCharacter(false)->id,
            'item_type' => $this->item->crafting_type,
            'item_id'   => $this->item->id,
        ])->see($this->item->name);
    }

    public function testCannotSeeComparePageForComparingShopItems() {

        $user = $this->character->getUser();

        Cache::shouldReceive('get')->with('shop-comparison-character-' . $user->character->id)->andReturn(null);
        Cache::shouldReceive('has')->with('shop-comparison-character-' . $user->character->id)->andReturn(false);
        Cache::shouldReceive('has')->with('character-attack-data-' . $user->character->id)->andReturn(true);
        Cache::shouldReceive('put')->andReturn(null);

        $this->actingAs($user)->visitRoute('game.shop.compare.item', [
            'character' => $this->character->getCharacter(false)->id,
            'item_type' => $this->item->crafting_type,
            'item_id'   => $this->item->id,
        ])->see('Comparison cache has expired. Please click compare again. Cache expires after 10 minutes');
    }

    public function testCannotBuyItemNoInventorySpace() {
        $user = $this->character->updateCharacter([
            'inventory_max' => 0
        ])->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.buy.item', ['character' => $this->character->getCharacter(false)->id]), [
            'item_id' => $this->item->id,
        ])->response;

        $response->assertSessionHas('error', 'Inventory is full. Please make room.');
    }

    public function testCannotBuyUnknownItem() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.buy.item', ['character' => $this->character->getCharacter(false)->id]), [
            'item_id' => 24867,
        ])->response;

        $response->assertSessionHas('error', 'Item not found.');
    }

    public function testCannotBuyItemNotEnoughGold() {

        $user = $this->character->updateCharacter(['gold' => 0])->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.buy.item', ['character' => $this->character->getCharacter(false)->id]), [
            'item_id' => $this->item->id,
        ])->response;

        $response->assertSessionHas('error', 'You do not have enough gold.');
    }

    public function testCannotBuyExpensiveItem() {
        $this->item->update([
            'cost' => 100,
        ]);

        $this->item->refresh();

        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.buy.item', ['character' => $this->character->getCharacter(false)->id]), [
            'item_id' => $this->item->id,
        ])->response;

        $response->assertSessionHas('error', 'You do not have enough gold.');
    }

    public function testCanSeeSellPage() {
        $user = $this->character->getUser();

        $this->actingAs($user)->visitRoute('game.shop.sell', ['character' => $this->character->getCharacter(false)->id])->see('Inventory');
    }

    public function testCanSellItem() {

        $user = $this->character->inventoryManagement()
                                ->unequipAll()
                                ->getCharacterFactory()
                                ->updateCharacter([
                                    'gold' => 0
                                ])
                                ->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.sell.item', ['character' => $this->character->getCharacter(false)->id]), [
            'slot_id' => InventorySlot::first()->id,
        ])->response;

        $sellFor = SellItemCalculator::fetchTotalSalePrice($this->item);

        $response->assertSessionHas('success', 'Sold: Rusty Dagger for: '.$sellFor.' gold.');

        $this->assertTrue($this->character->getCharacter(false)->gold > 0);
    }


    public function testCannotSellIteYouDontHave() {
        $user = $this->character->updateCharacter([
                                    'gold' => 0
                                ])
                                ->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.sell.item', ['character' => $this->character->getCharacter(false)->id]), [
            'slot_id' => '10',
        ])->response;

        $response->assertSessionHas('error', 'Item not found.');

        $this->assertFalse($this->character->getCharacter(false)->gold > 0);
    }

    public function testCannotSellAllWhenAllEquipped() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.sell.all', ['character' => $this->character->getCharacter(false)->id]))->response;

        $response->assertSessionHas('error', 'You have nothing that you can sell.');
    }

    public function testSellAllItems() {
        $user = $this->character->inventoryManagement()
                                ->unequipAll()
                                ->getCharacterFactory()
                                ->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.sell.all', ['character' => $this->character->getCharacter(false)->id]))->response;

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


        $response = $this->actingAs($user)->post(route('game.shop.sell.all', ['character' => $this->character->getCharacter(false)->id]))->response;

        $response->assertSessionHas('success', 'Sold all your unequipped items for a total of: 10 gold.');

        $questItems = $this->character->getCharacter(false)->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'quest';
        })->all();

        $this->assertFalse(empty($questItems));
    }

    public function testCanBulkBuy() {

        $user = $this->character->updateCharacter(['gold' => 100000000])->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.buy.bulk', ['character' => $this->character->getCharacter(false)->id]),
        [
            'items' => [$this->item->id]
        ])->response;

        $response->assertSessionHas('success', 'Your items are being purchased. 
        You can check your character sheet to see them come in. If you cannot afford the items, the game chat section will update.
        Once all items are purchased, the chat section will update to inform you.');
    }

    public function testFailToBulkBuyWhenNoGold() {

        Event::fake();

        $user = $this->character->updateCharacter(['gold' => 0])->getUser();

        $this->actingAs($user)->post(route('game.shop.buy.bulk', ['character' => $this->character->getCharacter(false)->id]), [
            'items' => [$this->item->id]
        ]);

        $character = $this->character->getCharacter(false);

        $this->assertEquals($character->inventory->slots()->where('item_id', $this->item->id)->count(), 1);
    }


    public function testFailToBulkBuyWhenNoItems() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.buy.bulk', ['character' => $this->character->getCharacter(false)->id]),[
            'items' => []
        ])->response;

        $response->assertSessionHas('error', 'No items could be found. Did you select any?');
    }

    public function testBuyAllSelectedItems() {
        Queue::fake();

        $user = $this->character->updateCharacter(['gold' => 5000])->getUser();

        $weapon = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => 6,
            'cost'        => 1,
        ]);

        $this->actingAs($user)->post(route('game.shop.buy.bulk', ['character' => $this->character->getCharacter(false)->id]), [
            'items' => [$this->item->id, $weapon->id]
        ])->response;

        Queue::assertPushed(PurchaseItemsJob::class);
    }

    public function testFailToSellInBulk() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.shop.sell.bulk', ['character' => $this->character->getCharacter(false)->id]), [
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
        $character = $this->character->getCharacter(false);


        $response = $this->actingAs($user)->post(route('game.shop.sell.bulk', ['character' => $this->character->getCharacter(false)->id]), [
            'slots' => $character->inventory->slots->filter(function($slot) {
                return !$slot->equipped && $slot->item->type !== 'quest';
            })->pluck('id')->toArray(),
        ])->response;

        $response->assertSessionHas('success', 'Sold selected items for: 10 gold.');
    }

    public function testCannotBuyAndReplaceCraftOnly() {
        $weapon = $this->createItem([
            'name'        => 'Sample Item',
            'type'        => 'weapon',
            'base_damage' => 6,
            'cost'        => 10,
            'craft_only'  => true
        ]);

        $user      = $this->character->getUser();

        $character = $this->character->getCharacter(false);


        $this->actingAs($user)->visitRoute('game.shop.buy', [
            'character' => $character
        ])->visitRoute('game.shop.compare.item', [
            'character' => $character,
            'item_id'   => $weapon->id,
            'item_type' => 'weapon',
        ])
            ->see($weapon->name)
            ->submitForm('Buy and Replace', [
                'position'       => 'right-hand',
                'slot_id'        => $character->inventory->slots->filter(function($slot) {
                    return $slot->equipped;
                })->first()->id,
                'equip_type'     => 'weapon',
                'item_id_to_buy' => $weapon->id,
            ])
            ->see('You are not capable of affording such luxury child!');
    }

    public function testCannotBuyAndReplaceNotEnoughGold() {
        $weapon = $this->createItem([
            'name'        => 'Sample Item',
            'type'        => 'weapon',
            'base_damage' => 6,
            'cost'        => 3000000000,
        ]);

        $user      = $this->character->getUser();

        $character = $this->character->getCharacter(false);

        $character->update([
            'inventory_max' => 0
        ]);

        $character = $character->refresh();


        $this->actingAs($user)->visitRoute('game.shop.buy', [
            'character' => $character
        ])->click('compare-item-' . $weapon->id)
            ->see($weapon->name)
            ->submitForm('Buy and Replace', [
                'position'       => 'right-hand',
                'slot_id'        => $character->inventory->slots->filter(function($slot) {
                    return $slot->equipped;
                })->first()->id,
                'equip_type'     => 'weapon',
                'item_id_to_buy' => $weapon->id,
            ])
            ->see('You do not have enough gold.');
    }

    public function testCannotBuyAndReplaceNoInventorySpace() {
        $weapon = $this->createItem([
            'name'        => 'Sample Item',
            'type'        => 'weapon',
            'base_damage' => 6,
            'cost'        => 10,
        ]);

        $user      = $this->character->getUser();

        $character = $this->character->getCharacter(false);

        $character->update([
            'inventory_max' => 0
        ]);

        $character = $character->refresh();


        $this->actingAs($user)->visitRoute('game.shop.buy', [
            'character' => $character
        ])->click('compare-item-' . $weapon->id)
            ->see($weapon->name)
            ->submitForm('Buy and Replace', [
                'position'       => 'right-hand',
                'slot_id'        => $character->inventory->slots->filter(function($slot) {
                    return $slot->equipped;
                })->first()->id,
                'equip_type'     => 'weapon',
                'item_id_to_buy' => $weapon->id,
            ])
            ->see('Inventory is full. Please make room.');
    }



    public function testBuyAndReplaceItem() {
        $weapon = $this->createItem([
            'name'        => 'Sample Item',
            'type'        => 'weapon',
            'base_damage' => 6,
            'cost'        => 10,
        ]);

        $user      = $this->character->getUser();

        $character = $this->character->getCharacter(false);


        $this->actingAs($user)->visitRoute('game.shop.buy', [
            'character' => $character
        ])->click('compare-item-' . $weapon->id)
            ->see($weapon->name)
            ->submitForm('Buy and Replace', [
                'position'       => 'right-hand',
                'slot_id'        => $character->inventory->slots->filter(function($slot) {
                    return $slot->equipped;
                })->first()->id,
                'equip_type'     => 'weapon',
                'item_id_to_buy' => $weapon->id,
            ])
            ->see('Purchased and equipped: ' . $weapon->affix_name . '.');
    }
}
