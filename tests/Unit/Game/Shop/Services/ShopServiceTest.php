<?php

namespace Tests\Unit\Game\Shop\Services;

use App\Game\Character\CharacterInventory\Exceptions\EquipItemException;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Shop\Services\ShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
