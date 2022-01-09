<?php

namespace Tests\Feature\Game\Core\Api;

use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Setup\Character\CharacterFactory;

class RandomEnchantControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateItemAffix;

    private $character;

    private $item;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->equipStartingEquipment();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetUniquesForTheQueenofHearts() {

        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
                'cost' => RandomAffixDetails::LEGENDARY,
                'randomly_generated' => true
            ])
        ]))->getCharacterFactory();

        $characterId = $character->getCharacter()->id;

        $response    = $this->actingAs($character->getUser())
                            ->json('GET', '/api/character/'.$characterId.'/inventory/uniques')
                            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertNotEmpty($content->slots);
        $this->assertFalse($content->has_gold);
        $this->assertEquals($character->getCharacter(false)->gold, $content->character_gold);
    }

    public function testPurchaseUnique() {
        $character = $this->character->getCharacter(false);

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $characterId = $character->id;

        $response    = $this->actingAs($character->user)
            ->json('POST', '/api/character/'.$characterId.'/random-enchant/purchase', [
                'type' => 'legendary'
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertNotEquals($character->gold, $content->gold);
        $this->assertTrue($content->item->market_sellable);
    }

    public function testFailToPurchaseUniqueWithFullInventory() {
        $character = $this->character->getCharacter(false);

        $character->update([
            'gold'          => MaxCurrenciesValue::MAX_GOLD,
            'inventory_max' => 0,
        ]);

        $character = $character->refresh();

        $characterId = $character->id;

        $response    = $this->actingAs($character->user)
            ->json('POST', '/api/character/'.$characterId.'/random-enchant/purchase', [
                'type' => 'legendary'
            ])
            ->response;

        $this->assertEquals(422, $response->status());
    }

    public function testFailToPurchaseUniqueNotEnoughGold() {
        $character = $this->character->getCharacter(false);

        $characterId = $character->id;

        $response    = $this->actingAs($character->user)
            ->json('POST', '/api/character/'.$characterId.'/random-enchant/purchase', [
                'type' => 'legendary'
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
    }

    public function testReRollUnique() {
        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'type' => 'suffix',
                    'cost' => RandomAffixDetails::LEGENDARY,
                    'randomly_generated' => true
                ])
            ])
        );

        $slotId = $character->getSlotId(0);


        $character = $character->getCharacter();

        $character->update([
            'gold_dust' => 2000000000,
            'shards'    => 2000000000,
        ]);

        $character = $character->refresh();

        $characterId = $character->id;

        $response    = $this->actingAs($character->user)
            ->json('POST', '/api/character/'.$characterId.'/random-enchant/reroll', [
                'selected_slot_id'     => $slotId,
                'selected_affix'       => 'all-enchantments',
                'selected_reroll_type' => 'stats',
                'gold_dust_cost'       => 150000,
                'shard_cost'           => 500,
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertNotEquals($character->gold_dust, $content->gold_dust);
        $this->assertNotEquals($character->shards, $content->shards);
    }

    public function testFailToReRollUniqueMissingItem() {
        $otherCharacter = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem(
            $this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'type' => 'suffix',
                    'cost' => RandomAffixDetails::LEGENDARY,
                    'randomly_generated' => true
                ])
            ])
        );

        $slotId = $otherCharacter->getSlotId(0);

        $character = $this->character->getCharacter(false);

        $character->update([
            'gold_dust' => 2000000000,
            'shards'    => 2000000000,
        ]);

        $character = $character->refresh();

        $characterId = $character->id;

        $response    = $this->actingAs($character->user)
            ->json('POST', '/api/character/'.$characterId.'/random-enchant/reroll', [
                'selected_slot_id'     => $slotId,
                'selected_affix'       => 'all-enchantments',
                'selected_reroll_type' => 'stats',
                'gold_dust_cost'       => 150000,
                'shard_cost'           => 500,
            ])
            ->response;

        $this->assertEquals(422, $response->status());
    }

    public function testFailToReRollUniqueMissingCurrency() {
        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'type' => 'suffix',
                    'cost' => RandomAffixDetails::LEGENDARY,
                    'randomly_generated' => true
                ])
            ])
        );

        $slotId = $character->getSlotId(0);


        $character = $character->getCharacter();

        $character = $character->refresh();

        $characterId = $character->id;

        $response    = $this->actingAs($character->user)
            ->json('POST', '/api/character/'.$characterId.'/random-enchant/reroll', [
                'selected_slot_id'     => $slotId,
                'selected_affix'       => 'all-enchantments',
                'selected_reroll_type' => 'stats',
                'gold_dust_cost'       => 150000,
                'shard_cost'           => 500,
            ])
            ->response;

        $this->assertEquals(422, $response->status());
    }

    public function testCanMoveAffixesToAnotherItem() {
        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'type' => 'suffix',
                    'cost' => RandomAffixDetails::LEGENDARY,
                    'randomly_generated' => true
                ])
            ])
        )->giveItem(
            $this->createItem([
                'name' => 'example',
                'type' => 'weapon',
            ])
        );

        $slotId = $character->getSlotId(0);
        $itemSlotid  = $character->getSlotId(1);

        $character = $character->getCharacter();

        $character->update([
            'gold'      => 2000000000000,
            'shards'    => 2000000000000
        ]);

        $character = $character->refresh();

        $characterId = $character->id;

        $response    = $this->actingAs($character->user)
            ->json('POST', '/api/character/'.$characterId.'/random-enchant/move', [
                'selected_slot_id'           => $slotId,
                'selected_secondary_slot_id' => $itemSlotid,
                'selected_affix'             => 'all-enchantments',
                'shard_cost'                 => 150000,
                'gold_cost'                  => 10000000000.,
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertNotEquals($character->gold, $content->gold);
        $this->assertNotEquals($character->shards, $content->shards);
    }

    public function testCannotMoveAffixesToAnotherItemBecauseItemIsMissing() {
        $otherCharacter = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem(
            $this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'type' => 'suffix',
                    'cost' => RandomAffixDetails::LEGENDARY,
                    'randomly_generated' => true
                ])
            ])
        )->giveItem(
            $this->createItem()
        );

        $slotId      = $otherCharacter->getSlotId(0);
        $itemSlotid  = $otherCharacter->getSlotId(1);

        $character = $this->character->getCharacter(false);

        $character->update([
            'gold_dust' => 2000000000,
            'shards'    => 2000000000,
        ]);

        $character = $character->refresh();

        $characterId = $character->id;

        $response    = $this->actingAs($character->user)
            ->json('POST', '/api/character/'.$characterId.'/random-enchant/move', [
                'selected_slot_id'           => $slotId,
                'selected_secondary_slot_id' => $itemSlotid,
                'selected_affix'             => 'all-enchantments',
                'shard_cost'                 => 150000,
                'gold_cost'                  => 10000000000.,
            ])
            ->response;

        $this->assertEquals(422, $response->status());
    }

    public function testCannotMoveAffixesToAnotherItemNotEnoughCurrencies() {
        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'type' => 'suffix',
                    'cost' => RandomAffixDetails::LEGENDARY,
                    'randomly_generated' => true
                ])
            ])
        )->giveItem(
            $this->createItem([
                'name' => 'example',
                'type' => 'weapon',
            ])
        );

        $slotId = $character->getSlotId(0);
        $itemSlotid  = $character->getSlotId(1);

        $character = $character->getCharacter();

        $characterId = $character->id;

        $response    = $this->actingAs($character->user)
            ->json('POST', '/api/character/'.$characterId.'/random-enchant/move', [
                'selected_slot_id'           => $slotId,
                'selected_secondary_slot_id' => $itemSlotid,
                'selected_affix'             => 'all-enchantments',
                'shard_cost'                 => 150000,
                'gold_cost'                  => 10000000000.,
            ])
            ->response;

        $this->assertEquals(422, $response->status());
    }
}
