<?php

namespace Tests\Unit\Game\Shop\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Shop\Services\GoblinShopService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class GoblinShopServiceTest extends TestCase {

    use RefreshDatabase, CreateItem;

    private ?CharacterFactory $character;

    private ?GoblinShopService $shopService;

    public function setUp(): void {
        parent::setUp();

        $this->character   = (new CharacterFactory())->createBaseCharacter()
                                                     ->givePlayerLocation()
                                                     ->kingdomManagement()
                                                     ->assignKingdom(['gold_bars' => 1000])
                                                     ->assignKingdom(['gold_bars' => 1000])
                                                     ->assignKingdom(['gold_bars' => 1000])
                                                     ->getCharacterFactory();

        $this->shopService = resolve(GoblinShopService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character   = null;
        $this->shopService = null;
    }

    public function testBuyItemWhereKingdomAbsorbsTheCost() {
        $item = $this->createItem(['gold_bars_cost' => 500]);

        $character = $this->character->getCharacter();

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $character = $character->refresh();

        $hasItem = $character->inventory->slots->filter(function($slot) {
            return $slot->item->gold_bars_cost > 0;
        })->first();

        $this->assertNotNull($hasItem);
        $this->assertNotNull($character->kingdoms->where('gold_bars', 500)->first());
    }

    public function testBuyItemWhereAllKingdomsAbsorbTheCost() {
        $item = $this->createItem(['gold_bars_cost' => 2000]);

        $character = $this->character->getCharacter();

        $this->shopService->buyItem($character, $item, $character->kingdoms()->get());

        $character = $character->refresh();

        $hasItem = $character->inventory->slots->filter(function($slot) {
            return $slot->item->gold_bars_cost > 0;
        })->first();

        $this->assertNotNull($hasItem);
        $this->assertCount(3, $character->kingdoms->where('gold_bars', '>', 0)->toArray());
    }

    public function testBuyReduceAllKingdomsToZero() {
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

        $hasItem = $character->inventory->slots->filter(function($slot) {
            return $slot->item->gold_bars_cost > 0;
        })->first();

        $this->assertNotNull($hasItem);
        $this->assertCount(0, $character->kingdoms->where('gold_bars', '>', 0)->toArray());
    }

    public function testPurchaseMultipleItemsTillAtFiftyRunesLeft() {
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

    public function testPurchaseMultipleItemsThatCostOneThousandTillZeroGoldBarsLeft() {
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

    public function testPurchaseWhenMultipleKingdomsHaveVariableGoldBars() {
        $item = $this->createItem(['gold_bars_cost' => 1000]);

        $character   = (new CharacterFactory())->createBaseCharacter()
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

        $hasItem = $character->inventory->slots->filter(function($slot) use ($item) {
           return $slot->item_id === $item->id;
        })->first();

        $this->assertNotNull($hasItem);
        $this->assertEquals(400, $character->kingdoms->sum('gold_bars'));
    }

    public function testPurchaseWhenMultipleKingdomsHaveVariableGoldBarsAndOneHasNoGoldBars() {
        $item = $this->createItem(['gold_bars_cost' => 1000]);

        $character   = (new CharacterFactory())->createBaseCharacter()
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

        $hasItem = $character->inventory->slots->filter(function($slot) use ($item) {
            return $slot->item_id === $item->id;
        })->first();

        $this->assertNotNull($hasItem);
        $this->assertEquals(400, $character->kingdoms->sum('gold_bars'));
    }
}
