<?php

namespace Tests\Unit\Game\SpecialtyShops\Services;

use App\Flare\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\SpecialtyShops\Services\SpecialtyShop;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class SpecialtyShopTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateItemAffix;

    private ?CharacterFactory $character;

    private ?SpecialtyShop $specialtyShop;

    public function setUp(): void {
        parent::setUp();

        $this->character     = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->specialtyShop = resolve(SpecialtyShop::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character     = null;
        $this->specialtyShop = null;
    }

    public function testCannotFindItemToPurchase() {
        $character = $this->character->getCharacter();

        $response = $this->specialtyShop->purchaseItem($character, 0, 'test');

        $this->assertEquals(422, $response['status']);
        $this->assertEquals('Item is not found.', $response['message']);
    }

    public function testDoesNotHaveTheCurrencyRequired() {
        $character = $this->character->getCharacter();

        $item = $this->createItem([
            'name'           => 'Special Item',
            'specialty_type' => ItemSpecialtyType::HELL_FORGED,
            'shards_cost'    => 1000,
            'gold_dust_cost' => 1000,
            'cost'           => 0,
        ]);

        $response = $this->specialtyShop->purchaseItem($character, $item->id, $item->specialty_type);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals('You do not have the currencies to purchase this.', $response['message']);
    }

    public function testDoesNotHaveTheBaseItemRequiredForUpgrade() {
        $character = $this->character->getCharacter();

        $item = $this->createItem([
            'name'           => 'Special Item',
            'specialty_type' => ItemSpecialtyType::HELL_FORGED,
            'shards_cost'    => 1000,
            'gold_dust_cost' => 1000,
            'cost'           => 0,
            'type'           => 'weapon',
        ]);

        $character->update(['shards' => 10000, 'gold_dust' => 10000]);

        $response = $this->specialtyShop->purchaseItem($character->refresh(), $item->id, $item->specialty_type);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals('You are missing an item of type: weapon with a crafting level of 400. Item must be in your inventory.', $response['message']);
    }

    public function testMissingPurgatoryChainGearToUpgrade() {
        $character = $this->character->getCharacter();

        $item = $this->createItem([
            'name'           => 'Special Item',
            'specialty_type' => ItemSpecialtyType::PURGATORY_CHAINS,
            'shards_cost'    => 1000,
            'gold_dust_cost' => 1000,
            'cost'           => 0,
            'type'           => 'weapon',
        ]);

        $character->update(['shards' => 10000, 'gold_dust' => 10000]);

        $response = $this->specialtyShop->purchaseItem($character->refresh(), $item->id, $item->specialty_type);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals('You are missing an item of type: weapon which must be of specialty type: '.ItemSpecialtyType::HELL_FORGED.'. Item must be in your inventory.', $response['message']);
    }

    public function testPurchaseHellForgedItem() {
        $item = $this->createItem([
            'skill_level_required' => 400,
            'skill_level_trivial'  => 401,
            'type'                 => 'weapon',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $item = $this->createItem([
            'name'           => 'Special Item',
            'specialty_type' => ItemSpecialtyType::HELL_FORGED,
            'shards_cost'    => 1000,
            'gold_dust_cost' => 1000,
            'cost'           => 0,
            'type'           => 'weapon',
        ]);

        $character->update(['shards' => 10000, 'gold_dust' => 10000]);

        $response = $this->specialtyShop->purchaseItem($character->refresh(), $item->id, $item->specialty_type);

        $character = $character->refresh();

        $this->assertEquals(200, $response['status']);
        $this->assertEquals(9000, $character->gold_dust);
        $this->assertEquals(9000, $character->shards);
        $this->assertCount(1, Item::where('name', $item->name)->where('specialty_type', ItemSpecialtyType::HELL_FORGED)->get());
    }

    public function testPurchasePurgatoryChainItem() {
        $item = $this->createItem([
            'skill_level_required' => 400,
            'skill_level_trivial'  => 401,
            'type'                 => 'weapon',
            'specialty_type'       => ItemSpecialtyType::HELL_FORGED,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $item = $this->createItem([
            'name'           => 'Special Item',
            'specialty_type' => ItemSpecialtyType::PURGATORY_CHAINS,
            'shards_cost'    => 1000,
            'gold_dust_cost' => 1000,
            'cost'           => 0,
            'type'           => 'weapon',
        ]);

        $character->update(['shards' => 10000, 'gold_dust' => 10000]);

        $response = $this->specialtyShop->purchaseItem($character->refresh(), $item->id, $item->specialty_type);

        $character = $character->refresh();

        $this->assertEquals(200, $response['status']);
        $this->assertEquals(9000, $character->gold_dust);
        $this->assertEquals(9000, $character->shards);
        $this->assertCount(1, Item::where('name', $item->name)->where('specialty_type', ItemSpecialtyType::PURGATORY_CHAINS)->get());
    }

    public function testMovesAffixesAndHolyOver() {
        $item = $this->createItem([
            'skill_level_required' => 400,
            'skill_level_trivial'  => 401,
            'type'                 => 'weapon',
            'specialty_type'       => ItemSpecialtyType::HELL_FORGED,
        ]);

        $itemSuffix = $this->createItemAffix([
            'type' => 'suffix'
        ]);

        $itemPrefix = $this->createItemAffix([
            'type' => 'prefix'
        ]);

        $item->update([
            'item_suffix_id' => $itemSuffix->id,
            'item_prefix_id' => $itemPrefix->id,
        ]);

        $item = $item->refresh();

        $item->appliedHolyStacks()->create([
            'item_id'                       => $item->id,
            'devouring_darkness_bonus'      => 0.10,
            'stat_increase_bonus'           => 0.10,
        ]);

        $item = $item->refresh();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $item = $this->createItem([
            'name'           => 'Special Item',
            'specialty_type' => ItemSpecialtyType::PURGATORY_CHAINS,
            'shards_cost'    => 1000,
            'gold_dust_cost' => 1000,
            'cost'           => 0,
            'type'           => 'weapon',
        ]);

        $character->update(['shards' => 10000, 'gold_dust' => 10000]);

        $response = $this->specialtyShop->purchaseItem($character->refresh(), $item->id, $item->specialty_type);

        $character = $character->refresh();

        $this->assertEquals(200, $response['status']);
        $this->assertEquals(9000, $character->gold_dust);
        $this->assertEquals(9000, $character->shards);

        $itemPurchased = $character->inventory->slots->first()->item;

        $this->assertNotNull($itemPurchased->item_suffix_id);
        $this->assertNotNull($itemPurchased->item_prefix_id);
        $this->assertNotEmpty($itemPurchased->appliedHolyStacks);
    }
}
