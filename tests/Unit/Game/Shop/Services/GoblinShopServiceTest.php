<?php

namespace Tests\Unit\Game\Shop\Services;

use App\Game\Shop\Services\GoblinShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class GoblinShopServiceTest extends TestCase
{
    use CreateItem, RefreshDatabase;

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

        $count = 1;

        foreach ($character->kingdoms as $kingdom) {

            if ($count === 3) {
                $kingdom->update(['gold_bars' => 334]);

                break;
            }

            $kingdom->update(['gold_bars' => 333]);

            $count++;
        }

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
}
