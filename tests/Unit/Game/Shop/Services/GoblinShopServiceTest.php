<?php

namespace Tests\Unit\Game\Shop\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\InventorySlot;
use App\Game\Shop\Services\GoblinShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateItem;

class GoblinShopServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?GoblinShopService $shopService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom(['gold_bars' => 1000])
            ->assignKingdom(['gold_bars' => 1000])
            ->assignKingdom(['gold_bars' => 1000])
            ->getCharacterFactory();

        $this->shopService = resolve(GoblinShopService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->shopService = null;
    }

    public function test_buy_item_where_kingdom_absorbs_the_cost()
    {
        $item = $this->createItem(['gold_bars_cost' => 500]);

        $character = $this->character->getCharacter();

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $character = $character->refresh();

        $hasItem = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->gold_bars_cost > 0;
        })->first();

        $this->assertNotNull($hasItem);
        $this->assertNotNull($character->kingdoms->where('gold_bars', 500)->first());
    }

    public function test_buy_item_where_all_kingdoms_absorb_the_cost()
    {
        $item = $this->createItem(['gold_bars_cost' => 2000]);

        $character = $this->character->getCharacter();

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $character = $character->refresh();

        $hasItem = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->gold_bars_cost > 0;
        })->first();

        $this->assertNotNull($hasItem);
        $this->assertCount(3, $character->kingdoms->where('gold_bars', '>', 0)->toArray());
    }

    public function test_buy_reduce_all_kingdoms_to_zero()
    {
        $item = $this->createItem(['gold_bars_cost' => 1000]);

        $character = $this->character->getCharacter();

        $kingdoms = $character->kingdoms;

        $kingdoms->get(0)->update(['gold_bars' => 333]);
        $kingdoms->get(1)->update(['gold_bars' => 333]);
        $kingdoms->get(2)->update(['gold_bars' => 334]);

        $character = $character->refresh();

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $character = $character->refresh();

        $hasItem = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->gold_bars_cost > 0;
        })->first();

        $this->assertNotNull($hasItem);
        $this->assertCount(0, $character->kingdoms->where('gold_bars', '>', 0)->toArray());
    }

    public function test_purchase_multiple_items_till_at_fifty_runes_left()
    {
        $item = $this->createItem(['gold_bars_cost' => 2000]);

        $character = $this->character->getCharacter();

        $character = $character->refresh();

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $character = $character->refresh();

        $item = $this->createItem(['gold_bars_cost' => 500]);

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $character = $character->refresh();

        $item = $this->createItem(['gold_bars_cost' => 250]);

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $character = $character->refresh();

        $item = $this->createItem(['gold_bars_cost' => 100]);

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $character = $character->refresh();

        $this->assertEquals(150, $character->kingdoms->sum('gold_bars'));
    }

    public function test_purchase_multiple_items_that_cost_one_thousand_till_zero_gold_bars_left()
    {
        $item = $this->createItem(['gold_bars_cost' => 2000]);

        $character = $this->character->getCharacter();

        $character = $character->refresh();

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $character = $character->refresh();

        $item = $this->createItem(['gold_bars_cost' => 1000]);

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $character = $character->refresh();

        $this->assertEquals(0, $character->kingdoms->sum('gold_bars'));
    }

    public function test_purchase_when_multiple_kingdoms_have_variable_gold_bars()
    {
        $item = $this->createItem(['gold_bars_cost' => 1000]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom(['gold_bars' => 500])
            ->assignKingdom(['gold_bars' => 500])
            ->assignKingdom(['gold_bars' => 400])
            ->getCharacterFactory();

        $character = $character->getCharacter();

        $character = $character->refresh();

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $character = $character->refresh();

        $hasItem = $character->inventory->slots->filter(function ($slot) use ($item) {
            return $slot->item_id === $item->id;
        })->first();

        $this->assertNotNull($hasItem);
        $this->assertEquals(400, $character->kingdoms->sum('gold_bars'));
    }

    public function test_purchase_when_multiple_kingdoms_have_variable_gold_bars_and_one_has_no_gold_bars()
    {
        $item = $this->createItem(['gold_bars_cost' => 1000]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom(['gold_bars' => 500])
            ->assignKingdom(['gold_bars' => 500])
            ->assignKingdom(['gold_bars' => 400])
            ->assignKingdom(['gold_bars' => 0])
            ->getCharacterFactory();

        $character = $character->getCharacter();

        $character = $character->refresh();

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $character = $character->refresh();

        $hasItem = $character->inventory->slots->filter(function ($slot) use ($item) {
            return $slot->item_id === $item->id;
        })->first();

        $this->assertNotNull($hasItem);
        $this->assertEquals(400, $character->kingdoms->sum('gold_bars'));
    }

    public function test_alchemy_item_purchase_writes_to_alchemy_bag_slot(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'gold_bars_cost' => 100]);

        $character = $this->character->getCharacter();

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $alchemyBag = $character->refresh()->alchemyBag;

        $this->assertNotNull($alchemyBag);
        $this->assertEquals(1, AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)->where('item_id', $item->id)->count());
        $this->assertEquals(1, AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)->where('item_id', $item->id)->value('amount'));
    }

    public function test_alchemy_item_purchase_increments_amount_when_same_item_exists(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'gold_bars_cost' => 100]);

        $character = $this->character->getCharacter();

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());
        $this->shopService->buyItem($character->refresh(), $item, $character->refresh()->kingdoms()->get());

        $alchemyBag = $character->refresh()->alchemyBag;

        $this->assertEquals(1, AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)->count());
        $this->assertEquals(2, AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)->where('item_id', $item->id)->value('amount'));
    }

    public function test_non_alchemy_item_purchase_writes_to_main_inventory(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'gold_bars_cost' => 100]);

        $character = $this->character->getCharacter();

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $character = $character->refresh();

        $inInventory = $character->inventory->slots->where('item_id', $item->id)->first();

        $this->assertNotNull($inInventory);
    }

    public function test_can_buy_alchemy_item_returns_true_when_bag_not_full(): void
    {
        $character = $this->character->getCharacter();

        $character->update(['alchemy_bag_limit' => 150]);

        $this->assertTrue($this->shopService->canBuyAlchemyItem($character->refresh()));
    }

    public function test_can_buy_alchemy_item_returns_false_when_bag_full(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'gold_bars_cost' => 100]);

        $character = $this->character->getCharacter();

        $character->update(['alchemy_bag_limit' => 1]);
        $character = $character->refresh();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $this->assertFalse($this->shopService->canBuyAlchemyItem($character->refresh()));
    }

    public function test_non_alchemy_item_purchase_creates_slot_with_correct_inventory_id(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'gold_bars_cost' => 100]);

        $character = $this->character->getCharacter();

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $slot = InventorySlot::where('item_id', $item->id)
            ->where('inventory_id', $character->inventory->id)
            ->first();

        $this->assertNotNull($slot);
        $this->assertEquals($character->inventory->id, $slot->inventory_id);
    }

    public function test_alchemy_purchase_succeeds_when_current_count_plus_one_equals_limit(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'gold_bars_cost' => 100]);

        $character = $this->character->getCharacter();

        $character->update(['alchemy_bag_limit' => 5]);
        $character = $character->refresh();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 4,
        ]);

        $this->shopService->buyItem($character->refresh(), $item, $character->refresh()->kingdoms()->get());

        $this->assertEquals(5, AlchemyBagSlot::where('alchemy_bag_id', $character->refresh()->alchemyBag->id)->sum('amount'));
    }

    public function test_alchemy_purchase_fails_when_current_count_plus_one_exceeds_limit(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'gold_bars_cost' => 100]);

        $character = $this->character->getCharacter();

        $character->update(['alchemy_bag_limit' => 5]);
        $character = $character->refresh();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'amount' => 5,
        ]);

        $this->shopService->buyItem($character->refresh(), $item, $character->refresh()->kingdoms()->get());

        $this->assertEquals(5, AlchemyBagSlot::where('alchemy_bag_id', $character->refresh()->alchemyBag->id)->sum('amount'));
    }

    public function test_alchemy_purchase_fails_when_stacking_existing_row_would_exceed_limit(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'gold_bars_cost' => 100]);

        $character = $this->character->getCharacter();

        $character->update(['alchemy_bag_limit' => 5]);
        $character = $character->refresh();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 5,
        ]);

        $this->shopService->buyItem($character->refresh(), $item, $character->refresh()->kingdoms()->get());

        $this->assertEquals(5, AlchemyBagSlot::where('alchemy_bag_id', $character->refresh()->alchemyBag->id)
            ->where('item_id', $item->id)
            ->value('amount'));
    }
}
