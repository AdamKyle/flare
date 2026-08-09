<?php

namespace Tests\Unit\Game\Shop\Services;

use App\Game\Character\CharacterInventory\Exceptions\EquipItemException;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Shop\Services\ShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ShopServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?ShopService $shopService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01,
            ]), 5
        )->givePlayerLocation();
        $this->shopService = resolve(ShopService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();

        $this->character = null;
        $this->shopService = null;
    }

    public function test_sell_all_items()
    {
        $trinket = $this->createItem(['type' => 'trinket']);
        $alchemy = $this->createItem(['type' => 'alchemy']);
        $quest = $this->createItem(['type' => 'quest']);
        $regular = $this->createItem(['type' => 'stave', 'cost' => 1000]);

        $character = $this->character->inventoryManagement()
            ->giveItem($trinket)
            ->giveItem($alchemy)
            ->giveItem($quest)
            ->giveItem($regular)
            ->getCharacter();

        $soldFor = $this->shopService->sellAllItems($character);

        $this->assertGreaterThan(0, $soldFor);

        $character = $character->refresh();

        $this->assertCount(3, $character->inventory->slots);
        $this->assertNotNull($character->inventory->slots->firstWhere('item_id', $trinket->id));
        $this->assertNotNull($character->inventory->slots->firstWhere('item_id', $alchemy->id));
        $this->assertNotNull($character->inventory->slots->firstWhere('item_id', $quest->id));
    }

    public function test_sell_all_items_with_no_items()
    {
        $trinket = $this->createItem(['type' => 'trinket']);
        $alchemy = $this->createItem(['type' => 'alchemy']);
        $quest = $this->createItem(['type' => 'quest']);

        $character = $this->character->inventoryManagement()
            ->giveItem($trinket)
            ->giveItem($alchemy)
            ->giveItem($quest)
            ->getCharacter();

        $response = $this->shopService->sellAllItems($character);

        $this->assertEquals('Could not sell any items ...', $response['message']);

        $character = $character->refresh();

        $this->assertCount(3, $character->inventory->slots);
        $this->assertNotNull($character->inventory->slots->firstWhere('item_id', $trinket->id));
        $this->assertNotNull($character->inventory->slots->firstWhere('item_id', $alchemy->id));
        $this->assertNotNull($character->inventory->slots->firstWhere('item_id', $quest->id));
    }

    public function test_buy_and_replace_item()
    {
        $existingShield = $this->createItem(['type' => 'shield']);
        $shield = $this->createItem(['type' => 'shield']);

        $character = $this->character->inventoryManagement()
            ->giveItem($existingShield, true, 'left-hand')
            ->getCharacter();

        $equippedSlot = $character->inventory->slots->firstWhere('item_id', $existingShield->id);

        $character->update(['gold' => 100000]);

        $this->shopService->buyAndReplace($shield, $character->refresh(), [
            'position' => 'left-hand',
            'slot_id' => $equippedSlot->id,
        ]);

        $character = $character->refresh();

        $inventorySlot = $character->inventory->slots->filter(function ($slot) use ($shield) {
            return $slot->item_id === $shield->id && $slot->equipped;
        })->first();

        $this->assertLessThan(100000, $character->gold);
        $this->assertNotNull($inventorySlot);
    }

    public function test_buy_multiple_items()
    {
        $shield = $this->createItem(['type' => 'shield']);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 100000]);

        $this->shopService->buyMultipleItems($character, $shield, 1000, 75);

        $character = $character->refresh();

        $this->assertLessThan(100000, $character->gold);
        $this->assertCount(75, $character->inventory->slots->toArray());
    }

    public function test_sell_item()
    {
        $shield = $this->createItem(['type' => 'shield']);
        $character = $this->character->inventoryManagement()->giveItem($shield)->getCharacter();

        $character->update(['gold' => 0]);

        $character = $character->refresh();

        $this->shopService->sellItem($character->inventory->slots()->where('item_id', $shield->id)->first(), $character);

        $character = $character->refresh();

        $this->assertNull($character->inventory->slots()->where('item_id', $shield->id)->first());
        $this->assertGreaterThan(0, $character->gold);
    }

    public function test_buy_and_replace_rejects_invalid_replacement_before_gold_or_inventory_changes(): void
    {
        $existingUniquePrefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true]);
        $existingUniqueItem = $this->createItem(['type' => 'shield', 'item_prefix_id' => $existingUniquePrefix->id]);
        $existingLeftHandShield = $this->createItem(['type' => 'shield']);

        $newUniquePrefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => true]);
        $newUniqueShield = $this->createItem(['type' => 'shield', 'item_prefix_id' => $newUniquePrefix->id, 'cost' => 1000]);

        $character = $this->character->inventoryManagement()
            ->giveItem($existingUniqueItem, true, 'right-hand')
            ->giveItem($existingLeftHandShield, true, 'left-hand')
            ->getCharacter();

        $equippedSlot = $character->inventory->slots->firstWhere('item_id', $existingLeftHandShield->id);

        $character->update(['gold' => 50000]);
        $character = $character->refresh();

        try {
            $this->shopService->buyAndReplace($newUniqueShield, $character, [
                'position' => 'left-hand',
                'slot_id' => $equippedSlot->id,
            ]);

            $this->fail('Expected the invalid replacement to be rejected.');
        } catch (EquipItemException $exception) {
            $this->assertSame('Cannot equip another unique.', $exception->getMessage());
        }

        $character = $character->refresh();

        $this->assertSame(50000, $character->gold);
        $this->assertNull($character->inventory->slots->firstWhere('item_id', $newUniqueShield->id));
    }

    public function test_get_items_for_shop_returns_standard_paginated_shape()
    {
        $this->createItem(['type' => 'shield', 'cost' => 100]);

        $character = $this->character->getCharacter();

        $response = $this->shopService->getItemsForShop($character, 'shield', null);

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('can_load_more', $response['meta']);
        $this->assertSame(100, $response['data'][0]['cost']);
    }

    public function test_get_items_for_shop_filters_by_search_text()
    {
        $this->createItem(['type' => 'shield', 'name' => 'Rusty Buckler']);
        $this->createItem(['type' => 'shield', 'name' => 'Golden Aegis']);

        $character = $this->character->getCharacter();

        $response = $this->shopService->getItemsForShop($character, 'shield', 'golden');

        $this->assertCount(1, $response['data']);
        $this->assertSame('Golden Aegis', $response['data'][0]['name']);
    }

    public function test_get_items_for_shop_filters_by_type()
    {
        $this->createItem(['type' => 'shield']);
        $this->createItem(['type' => 'hammer']);

        $character = $this->character->getCharacter();

        $response = $this->shopService->getItemsForShop($character, 'hammer', null);

        $this->assertCount(1, $response['data']);
        $this->assertSame('hammer', $response['data'][0]['type']);
    }

    public function test_get_items_for_shop_sorts_cost_low_to_high_by_default()
    {
        $this->createItem(['type' => 'shield', 'cost' => 500]);
        $this->createItem(['type' => 'shield', 'cost' => 100]);

        $character = $this->character->getCharacter();

        $response = $this->shopService->getItemsForShop($character, 'shield', null);

        $this->assertSame(100, $response['data'][0]['cost']);
        $this->assertSame(500, $response['data'][1]['cost']);
    }

    public function test_get_items_for_shop_sorts_cost_high_to_low_when_requested()
    {
        $this->createItem(['type' => 'shield', 'cost' => 500]);
        $this->createItem(['type' => 'shield', 'cost' => 100]);

        $character = $this->character->getCharacter();

        $response = $this->shopService->getItemsForShop($character, 'shield', null, 'desc');

        $this->assertSame(500, $response['data'][0]['cost']);
        $this->assertSame(100, $response['data'][1]['cost']);
    }

    public function test_get_items_for_shop_filters_by_multiple_types_when_type_is_not_provided()
    {
        $this->createItem(['type' => 'stave', 'cost' => 100]);
        $this->createItem(['type' => 'hammer', 'cost' => 100]);

        $hereticCharacter = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Heretic'])->givePlayerLocation()->getCharacter();

        $response = $this->shopService->getItemsForShop($hereticCharacter, null, null);

        $this->assertCount(1, $response['data']);
        $this->assertSame('stave', $response['data'][0]['type']);
    }

    public function test_get_items_for_shop_excludes_quest_items_even_when_explicitly_filtered()
    {
        $this->createItem(['type' => 'quest']);

        $character = $this->character->getCharacter();

        $response = $this->shopService->getItemsForShop($character, 'quest', null);

        $this->assertCount(0, $response['data']);
    }

    public function test_sell_item_do_not_go_above_max_gold()
    {
        $shield = $this->createItem(['type' => 'shield']);
        $character = $this->character->inventoryManagement()->giveItem($shield)->getCharacter();

        $character->update(['gold' => CurrencyLimit::MAX_GOLD]);

        $character = $character->refresh();

        $this->shopService->sellItem($character->inventory->slots()->where('item_id', $shield->id)->first(), $character);

        $character = $character->refresh();

        $this->assertNull($character->inventory->slots()->where('item_id', $shield->id)->first());
        $this->assertEquals(CurrencyLimit::MAX_GOLD, $character->gold);
    }

    public function test_sell_specific_item_returns_error_when_slot_not_found()
    {
        $character = $this->character->getCharacter();

        $result = $this->shopService->sellSpecificItem($character, 999999);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Item not found.', $result['message']);
    }

    public function test_sell_specific_item_returns_error_when_item_is_trinket_or_artifact()
    {
        $trinket = $this->createItem(['type' => 'trinket']);
        $character = $this->character->inventoryManagement()->giveItem($trinket)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $trinket->id)->first();

        $result = $this->shopService->sellSpecificItem($character, $slot->id);

        $this->assertSame('The shop keeper will not accept this item (Trinkets/Artifacts cannot be sold to the shop).', $result['message']);
    }

    public function test_sell_specific_item_sells_item_and_returns_success_result()
    {
        $shield = $this->createItem(['type' => 'shield']);
        $character = $this->character->inventoryManagement()->giveItem($shield)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $shield->id)->first();

        $result = $this->shopService->sellSpecificItem($character, $slot->id);

        $this->assertStringStartsWith('Sold:', $result['message']);
        $this->assertNull($character->refresh()->inventory->slots()->where('item_id', $shield->id)->first());
    }

    public function test_sell_all_items_caps_gold_at_max_gold()
    {
        $expensiveItem = $this->createItem(['type' => 'stave', 'cost' => 1000000]);
        $character = $this->character->inventoryManagement()->giveItem($expensiveItem)->getCharacter();

        $character->update(['gold' => CurrencyLimit::MAX_GOLD]);
        $character = $character->refresh();

        $this->shopService->sellAllItems($character);

        $this->assertSame(CurrencyLimit::MAX_GOLD, $character->refresh()->gold);
    }

    public function test_buy_multiple_items_does_nothing_when_amount_is_less_than_one()
    {
        $shield = $this->createItem(['type' => 'shield']);
        $character = $this->character->getCharacter();
        $character->update(['gold' => 1000]);

        $this->shopService->buyMultipleItems($character, $shield, 500, 0);

        $character = $character->refresh();

        $this->assertSame(1000, $character->gold);
        $this->assertCount(0, $character->inventory->slots()->where('item_id', $shield->id)->get());
    }

    public function test_buy_and_replace_does_nothing_when_replacement_slot_does_not_exist()
    {
        $shield = $this->createItem(['type' => 'shield']);
        $character = $this->character->getCharacter();
        $character->update(['gold' => 100000]);

        $this->shopService->buyAndReplace($shield, $character->refresh(), [
            'position' => 'left-hand',
            'slot_id' => 999999,
        ]);

        $character = $character->refresh();

        $this->assertCount(0, $character->inventory->slots()->where('item_id', $shield->id)->get());
    }

    public function test_buy_and_replace_does_not_equip_when_purchased_item_slot_is_not_created()
    {
        Event::fake([BuyItemEvent::class]);

        $existingShield = $this->createItem(['type' => 'shield']);
        $newShield = $this->createItem(['type' => 'shield']);

        $character = $this->character->inventoryManagement()
            ->giveItem($existingShield, true, 'left-hand')
            ->getCharacter();

        $equippedSlot = $character->inventory->slots->firstWhere('item_id', $existingShield->id);

        $character->update(['gold' => 100000]);

        $this->shopService->buyAndReplace($newShield, $character->refresh(), [
            'position' => 'left-hand',
            'slot_id' => $equippedSlot->id,
        ]);

        $character = $character->refresh();

        $this->assertNotNull($character->inventory->slots->firstWhere('item_id', $existingShield->id));
        $this->assertNull($character->inventory->slots->firstWhere('item_id', $newShield->id));
    }

    public function test_auto_sell_item_updates_gold_when_under_max_gold()
    {
        $item = $this->createItem(['type' => 'shield']);
        $character = $this->character->getCharacter();
        $character->update(['gold' => 0]);

        $updatedCharacter = $this->shopService->autoSellItem($character->refresh(), $item);

        $this->assertGreaterThan(0, $updatedCharacter->gold);
    }

    public function test_auto_sell_item_caps_gold_at_max_gold()
    {
        $item = $this->createItem(['type' => 'shield']);
        $character = $this->character->getCharacter();
        $character->update(['gold' => CurrencyLimit::MAX_GOLD]);

        $updatedCharacter = $this->shopService->autoSellItem($character->refresh(), $item);

        $this->assertSame(CurrencyLimit::MAX_GOLD, $updatedCharacter->gold);
    }
}
