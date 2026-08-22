<?php

namespace Tests\Unit\Game\Market\Services;

use App\Flare\Models\MarketBoard as MarketBoardModel;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Market\Services\MarketBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class MarketBoardBatchCraftingTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?MarketBoard $marketBoard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marketBoard = resolve(MarketBoard::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->marketBoard = null;
    }

    public function test_list_batch_crafted_item_creates_a_market_listing_at_the_requested_price(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem();

        $this->marketBoard->listBatchCraftedItem($character, $item, 5000);

        $listing = MarketBoardModel::where('character_id', $character->id)->where('item_id', $item->id)->first();

        $this->assertNotNull($listing);
        $this->assertSame(5000, $listing->listed_price);
    }

    public function test_list_batch_crafted_item_clamps_to_the_maximum_gold_currency_limit(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem();

        $this->marketBoard->listBatchCraftedItem($character, $item, CurrencyLimit::MAX_GOLD + 1000);

        $listing = MarketBoardModel::where('character_id', $character->id)->where('item_id', $item->id)->first();

        $this->assertSame(CurrencyLimit::MAX_GOLD, $listing->listed_price);
    }
}
