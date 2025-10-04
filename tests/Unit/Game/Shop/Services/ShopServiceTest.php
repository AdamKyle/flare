<?php

namespace Tests\Unit\Game\Shop\Services;

use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Shop\Services\ShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class ShopServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

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
        $invalidItemTypes = ['trinket', 'alchemy', 'quest'];

        // character should still have these:
        foreach ($character->inventory->slots as $slot) {
            $this->assertTrue(in_array($slot->item->type, $invalidItemTypes));
        }
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
        $invalidItemTypes = ['trinket', 'alchemy', 'quest'];

        // character should still have these:
        foreach ($character->inventory->slots as $slot) {
            $this->assertTrue(in_array($slot->item->type, $invalidItemTypes));
        }
    }

    public function test_buy_and_replace_item()
    {
        $shield = $this->createItem(['type' => 'shield']);

        $character = $this->character->getCharacter();

        $character->update(['gold' => 100000]);

        $this->shopService->buyAndReplace($shield, $character->refresh(), [
            'position' => 'left-hand',
        ]);

        $character = $character->refresh();

        $inventorySlot = $character->inventory->slots->filter(function ($slot) use ($shield) {
            return $slot->item_id === $shield->id && $slot->equipped;
        })->first();

        $this->assertLessThan(100000, $character->fold);
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

    public function test_sell_item_do_not_go_above_max_gold()
    {
        $shield = $this->createItem(['type' => 'shield']);
        $character = $this->character->inventoryManagement()->giveItem($shield)->getCharacter();

        $character->update(['gold' => MaxCurrenciesValue::MAX_GOLD]);

        $character = $character->refresh();

        $this->shopService->sellItem($character->inventory->slots()->where('item_id', $shield->id)->first(), $character);

        $character = $character->refresh();

        $this->assertNull($character->inventory->slots()->where('item_id', $shield->id)->first());
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $character->gold);
    }
}
