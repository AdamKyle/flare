<?php

namespace Tests\Feature\Game\Core;

use Facades\App\Flare\Calculators\SellItemCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;
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
        

        $this->character = (new CharacterSetup())
                                ->setupCharacter($this->createUser())
                                ->givePlayerLocation()
                                ->giveItem($this->item)
                                ->equipLeftHand()
                                ->getCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;

        $this->item = null;

        $this->itemAffix = null;
    }

    public function testCanSeeShop() {
        $this->actingAs($this->character->user)->visitRoute('game.shop.buy')->see('Buying');
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

        $sellFor = SellItemCalculator::fetchTotalSalePrice($this->item);

        $response->assertSessionHas('success', 'Sold: Rusty Dagger for: '.$sellFor.' gold.');

        $this->character->refresh();

        $this->assertTrue($this->character->gold > 0);
    }

    public function testCanSellItemWithArtifactAndAfixes() {
        $this->character->update([
            'gold' => 0,
        ]);


        $this->item->update([
            'item_suffix_id' => $this->itemAffix->id,
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

        $sellFor = SellItemCalculator::fetchTotalSalePrice($this->item);

        $response->assertSessionHas('success', 'Sold: Rusty Dagger *'.$this->itemAffix->name.'* for: '.$sellFor.' gold.');

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

    public function testCannotSellAllWhenAllEquipped() {
        $response = $this->actingAs($this->character->user)->post(route('game.shop.sell.all'))->response;

        $response->assertSessionHas('error', 'You have nothing that you can sell.');
    }

    public function testSellAllItems() {
        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $this->item->id,
            'equipped'     => false,
            'position'     => null,
        ]);

        $response = $this->actingAs($this->character->user)->post(route('game.shop.sell.all'))->response;

        $response->assertSessionHas('success', 'Sold all your unequipped items for a total of: 8 gold.');
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

        $this->character->inventory->slots()->insert([
            [
                'inventory_id' => $this->character->inventory->id,
                'item_id'      => $item->id,
                'equipped'     => false,
                'position'     => null,
            ],
            [
                'inventory_id' => $this->character->inventory->id,
                'item_id'      => $weapon->id,
                'equipped'     => false,
                'position'     => null,
            ]
        ]);

        $response = $this->actingAs($this->character->user)->post(route('game.shop.sell.all'))->response;

        $response->assertSessionHas('success', 'Sold all your unequipped items for a total of: 8 gold.');

        $questItems = $this->character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'quest';
        })->all();

        $this->assertFalse(empty($questItems));
    }

    public function testFailToBulkBuyWhenNoGold() {
        $this->character->update([
            'gold' => 0
        ]);

        $response = $this->actingAs($this->character->user)->post(route('game.shop.buy.bulk', [
            'items' => [$this->item->id]
        ]))->response;

        $response->assertSessionHas('error', 'You do not have enough gold.');
    }

    public function testFailToBulkBuyWhenNoItems() {
        $this->character->update([
            'gold' => 100
        ]);

        $response = $this->actingAs($this->character->user)->post(route('game.shop.buy.bulk', [
            'items' => []
        ]))->response;

        $response->assertSessionHas('error', 'No items could be found. Did you select any?');
    }

    public function testNotEnoughGold() {
        $this->character->update([
            'gold' => 10
        ]);

        $weapon = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => 6,
            'cost'        => 10,
        ]);

        $response = $this->actingAs($this->character->user)->post(route('game.shop.buy.bulk', [
            'items' => [$this->item->id, $weapon->id]
        ]))->response;

        $response->assertSessionHas('error', 'You do not have enough gold to buy: ' . $this->item->name . '. Anything before this item in the list was purchased.');

        $count = $this->character->refresh()->inventory->slots->count();
        
        $this->assertEquals($count, 2);
    }

    public function testBuyAllSelectedItems() {
        $this->character->update([
            'gold' => 1000
        ]);

        $weapon = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => 6,
            'cost'        => 10,
        ]);

        $response = $this->actingAs($this->character->user)->post(route('game.shop.buy.bulk', [
            'items' => [$this->item->id, $weapon->id]
        ]))->response;

        $response->assertSessionHas('success', 'Puchased all selected items.');

        $count = $this->character->refresh()->inventory->slots->count();
        
        $this->assertEquals($count, 3);
    }

    public function testFailToSellInBulk() {
        $response = $this->actingAs($this->character->user)->post(route('game.shop.sell.bulk', [
            'slots' => []
        ]))->response;

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

        $this->character->inventory->slots()->insert([
            [
                'inventory_id' => $this->character->inventory->id,
                'item_id'      => $item->id,
                'equipped'     => false,
                'position'     => null,
            ],
            [
                'inventory_id' => $this->character->inventory->id,
                'item_id'      => $weapon->id,
                'equipped'     => false,
                'position'     => null,
            ]
        ]);

        
        $response = $this->actingAs($this->character->user)->post(route('game.shop.sell.bulk', [
            'slots' => $this->character->refresh()->inventory->slots->filter(function($slot) {
                return !$slot->equipped && $slot->item->type !== 'quest';
            })->pluck('id')->toArray(),
        ]))->response;

        $response->assertSessionHas('success', 'Sold selected items for: 8 gold.');
    }
}
