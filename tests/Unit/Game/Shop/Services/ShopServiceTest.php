<?php

namespace Tests\Unit\Game\Shop\Services;

use App\Flare\Pagination\Pagination;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Character\CharacterAttack\Transformers\CharacterAttackTransformer;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
use App\Game\Character\CharacterInventory\Transformers\InventorySetOptionTransformer;
use App\Game\Character\CharacterInventory\Transformers\InventoryTransformer;
use App\Game\Character\CharacterInventory\Validations\SetHandsValidation;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\PhpRandomNumberGenerator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Items\Enricher\EquippableEnricher;
use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer as ApiUsableItemTransformer;
use App\Game\Core\Items\Transformers\EquippableItemTransformer;
use App\Game\Core\Items\Transformers\ItemTransformer;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Core\Items\Transformers\UsableItemTransformer;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\SkillBonusContextService;
use App\Game\Skills\Services\SkillBonusService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use League\Fractal\Manager;
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

        $manager = new Manager;
        $randomNumberGenerator = new PhpRandomNumberGenerator;
        $equippableItemTransformer = new EquippableItemTransformer;
        $questItemTransformer = new QuestItemTransformer;
        $inventorySetService = new InventorySetService(new SetHandsValidation);
        $skillBonusService = new SkillBonusService(new SkillBonusContextService);

        $itemEnricherFactory = new ItemEnricherFactory(
            new EquippableEnricher,
            $equippableItemTransformer,
            new UsableItemTransformer,
            $questItemTransformer,
            new PlainDataSerializer,
            $manager,
        );

        $this->shopService = new ShopService(
            new EquipItemService($manager, new CharacterAttackTransformer, $inventorySetService),
            new CharacterInventoryService(
                $itemEnricherFactory,
                $equippableItemTransformer,
                $questItemTransformer,
                new ApiUsableItemTransformer,
                new InventoryTransformer($itemEnricherFactory),
                $inventorySetService,
                new MassDisenchantService(new SkillCheckService($randomNumberGenerator, $skillBonusService), $randomNumberGenerator, new ChanceCalculator($randomNumberGenerator), $skillBonusService),
                Mockery::mock(UpdateCharacterSkillsService::class),
                Mockery::mock(DisenchantService::class),
                new Pagination($manager),
                $manager,
                new InventorySetOptionTransformer,
            ),
            new CharacterInventoryCountTransformer,
            new ItemTransformer($itemEnricherFactory),
            new Pagination($manager),
        );
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

        $result = $this->shopService->purchaseAndReplace($character->refresh(), $shield, [
            'position' => 'left-hand',
            'slot_id' => $equippedSlot->id,
        ]);

        $character = $character->refresh();

        $inventorySlot = $character->inventory->slots->filter(function ($slot) use ($shield) {
            return $slot->item_id === $shield->id && $slot->equipped;
        })->first();

        $this->assertSame(200, $result['status']);
        $this->assertLessThan(100000, $character->gold);
        $this->assertNotNull($inventorySlot);
    }

    public function test_buy_multiple_items()
    {
        $shield = $this->createItem(['type' => 'shield', 'cost' => 1000]);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 100000, 'inventory_max' => 75]);

        $this->shopService->purchaseMultiple($character->refresh(), $shield, 75);

        $character = $character->refresh();

        $this->assertSame(25000, $character->gold);
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

    public function test_buy_and_replace_with_another_unique_item_is_rejected(): void
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

        $result = $this->shopService->purchaseAndReplace($character, $newUniqueShield, [
            'position' => 'left-hand',
            'slot_id' => $equippedSlot->id,
        ]);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Could not complete purchase.', $result['message']);
        $this->assertSame(50000, $character->refresh()->gold);
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

    public function test_buy_multiple_items_rejects_more_items_than_the_inventory_can_hold()
    {
        $shield = $this->createItem(['type' => 'shield', 'cost' => 100]);
        $character = $this->character->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 1]);

        $result = $this->shopService->purchaseMultiple($character->refresh(), $shield, 2);

        $this->assertSame('You cannot purchase more then you have inventory space.', $result['message']);
        $this->assertSame(1000, $character->refresh()->gold);
    }

    public function test_buy_and_replace_rejects_a_replacement_slot_that_does_not_exist()
    {
        $shield = $this->createItem(['type' => 'shield']);
        $character = $this->character->getCharacter();
        $character->update(['gold' => 100000]);

        $result = $this->shopService->purchaseAndReplace($character->refresh(), $shield, [
            'position' => 'left-hand',
            'slot_id' => 999999,
        ]);

        $character = $character->refresh();

        $this->assertSame('The equipped item you chose to replace could not be found.', $result['message']);
        $this->assertCount(0, $character->inventory->slots()->where('item_id', $shield->id)->get());
    }

    public function test_purchase_item_charges_gold_and_adds_the_item_to_the_inventory()
    {
        $shield = $this->createItem(['type' => 'shield', 'cost' => 1000]);
        $character = $this->character->getCharacter();
        $character->update(['gold' => 5000]);

        $result = $this->shopService->purchaseItem($character->refresh(), $shield);

        $character = $character->refresh();

        $this->assertSame(200, $result['status']);
        $this->assertSame(4000, $character->gold);
        $this->assertNotNull($character->inventory->slots->firstWhere('item_id', $shield->id));
    }

    public function test_purchase_item_charges_a_merchant_the_discounted_price()
    {
        $shield = $this->createItem(['type' => 'shield', 'cost' => 10]);
        $merchant = (new CharacterFactory)->createBaseCharacter([], ['name' => 'Merchant'])->givePlayerLocation()->getCharacter();
        $merchant->update(['gold' => 10]);

        $this->shopService->purchaseItem($merchant->refresh(), $shield);

        $this->assertSame(3, $merchant->refresh()->gold);
    }

    public function test_purchase_item_announces_the_purchase_after_the_character_is_charged()
    {
        Event::fake([BuyItemEvent::class]);

        $shield = $this->createItem(['type' => 'shield', 'cost' => 1000]);
        $character = $this->character->getCharacter();
        $character->update(['gold' => 5000]);

        $this->shopService->purchaseItem($character->refresh(), $shield);

        Event::assertDispatched(BuyItemEvent::class, function (BuyItemEvent $event) use ($shield): bool {
            return $event->character->gold === 4000
                && $event->character->inventory->slots()->where('item_id', $shield->id)->exists();
        });
    }

    public function test_purchase_item_the_character_cannot_afford_is_not_announced()
    {
        Event::fake([BuyItemEvent::class]);

        $shield = $this->createItem(['type' => 'shield', 'cost' => 1000]);
        $character = $this->character->getCharacter();
        $character->update(['gold' => 500]);

        $result = $this->shopService->purchaseItem($character->refresh(), $shield);

        $this->assertSame('You do not have enough gold.', $result['message']);
        Event::assertNotDispatched(BuyItemEvent::class);
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
