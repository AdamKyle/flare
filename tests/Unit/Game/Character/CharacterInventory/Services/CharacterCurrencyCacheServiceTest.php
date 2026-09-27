<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Item;
use App\Game\Character\CharacterInventory\Services\CharacterCurrencyCacheService;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Currency\Values\CurrencyCacheType;
use App\Game\Kingdoms\Contracts\CharacterGoldBarDeposit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateItem;

class CharacterCurrencyCacheServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character = null;

    private ?MockInterface $characterGoldBarDeposit = null;

    private ?CharacterCurrencyCacheService $characterCurrencyCacheService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->characterGoldBarDeposit = Mockery::mock(CharacterGoldBarDeposit::class);

        $this->characterCurrencyCacheService = new CharacterCurrencyCacheService(
            $this->characterGoldBarDeposit,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();

        $this->character = null;

        $this->characterGoldBarDeposit = null;

        $this->characterCurrencyCacheService = null;
    }

    public function test_rejects_a_cache_slot_not_owned_by_the_character(): void
    {
        $character = $this->character->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $cacheItem = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'currency_cache_type' => CurrencyCacheType::GOLD,
            'cache_amount' => 1000,
        ]);
        $otherSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $otherCharacter->alchemyBag->id,
            'character_id' => $otherCharacter->id,
            'item_id' => $cacheItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterCurrencyCacheService->useCache($character, $otherSlot);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No. Not yours!', $result['message']);
        $this->assertEquals(1000, $cacheItem->refresh()->cache_amount);
    }

    public function test_rejects_an_alchemy_item_that_is_not_a_cache(): void
    {
        $character = $this->character->getCharacter();
        $alchemyItem = $this->createItem(['type' => 'alchemy', 'usable' => true]);
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $alchemyItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterCurrencyCacheService->useCache($character, $slot);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('That Alchemy Item is not a Compensation Cache.', $result['message']);
        $this->assertNotNull($slot->fresh());
    }

    public function test_partially_withdraws_gold_near_the_cap_and_keeps_the_exact_remainder(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['gold' => CurrencyLimit::MAX_GOLD - 400]);
        $cacheItem = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'currency_cache_type' => CurrencyCacheType::GOLD,
            'cache_amount' => 1000,
        ]);
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $cacheItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterCurrencyCacheService->useCache($character->refresh(), $slot);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(400, $result['withdrawn_amount']);
        $this->assertEquals(600, $result['remaining_cache_amount']);
        $this->assertEquals(CurrencyLimit::MAX_GOLD, $character->refresh()->gold);
        $this->assertEquals(600, $cacheItem->refresh()->cache_amount);
        $this->assertNotNull($slot->fresh());
    }

    public function test_fully_withdrawing_a_cache_hard_deletes_the_slot_and_cache_item(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['gold_dust' => 100]);
        $cacheItem = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'currency_cache_type' => CurrencyCacheType::GOLD_DUST,
            'cache_amount' => 2500,
        ]);
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $cacheItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterCurrencyCacheService->useCache($character->refresh(), $slot);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(2500, $result['withdrawn_amount']);
        $this->assertEquals(0, $result['remaining_cache_amount']);
        $this->assertEquals(2600, $character->refresh()->gold_dust);
        $this->assertNull(AlchemyBagSlot::find($slot->id));
        $this->assertNull(Item::find($cacheItem->id));
    }

    public function test_does_not_consume_the_cache_when_the_currency_is_capped(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['shards' => CurrencyLimit::MAX_SHARDS]);
        $cacheItem = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'currency_cache_type' => CurrencyCacheType::SHARDS,
            'cache_amount' => 750,
        ]);
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $cacheItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterCurrencyCacheService->useCache($character->refresh(), $slot);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You are already holding the maximum amount of Celestial Shards. Nothing was withdrawn from this cache.', $result['message']);
        $this->assertEquals(CurrencyLimit::MAX_SHARDS, $character->refresh()->shards);
        $this->assertEquals(750, $cacheItem->refresh()->cache_amount);
        $this->assertNotNull($slot->fresh());
    }

    public function test_fully_deposited_gold_bar_cache_hard_deletes_the_slot_and_cache_item(): void
    {
        $character = $this->character->getCharacter();
        $cacheItem = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'currency_cache_type' => CurrencyCacheType::GOLD_BARS,
            'cache_amount' => 500,
        ]);
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $cacheItem->id,
            'amount' => 1,
        ]);

        $this->characterGoldBarDeposit
            ->shouldReceive('deposit')
            ->once()
            ->with($character->id, 500)
            ->andReturn(500);

        $result = $this->characterCurrencyCacheService->useCache($character, $slot);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(500, $result['withdrawn_amount']);
        $this->assertEquals(0, $result['remaining_cache_amount']);
        $this->assertNull(Item::find($cacheItem->id));
        $this->assertNull(AlchemyBagSlot::find($slot->id));
    }

    public function test_partially_deposited_gold_bar_cache_keeps_the_exact_remainder(): void
    {
        $character = $this->character->getCharacter();
        $cacheItem = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'currency_cache_type' => CurrencyCacheType::GOLD_BARS,
            'cache_amount' => 2000,
        ]);
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $cacheItem->id,
            'amount' => 1,
        ]);

        $this->characterGoldBarDeposit
            ->shouldReceive('deposit')
            ->once()
            ->with($character->id, 2000)
            ->andReturn(250);

        $result = $this->characterCurrencyCacheService->useCache($character, $slot);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(250, $result['withdrawn_amount']);
        $this->assertEquals(1750, $result['remaining_cache_amount']);
        $this->assertEquals(1750, $cacheItem->refresh()->cache_amount);
        $this->assertNotNull($slot->fresh());
    }

    public function test_gold_bar_cache_is_unchanged_when_nothing_is_deposited(): void
    {
        $character = $this->character->getCharacter();
        $cacheItem = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'currency_cache_type' => CurrencyCacheType::GOLD_BARS,
            'cache_amount' => 2000,
        ]);
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $cacheItem->id,
            'amount' => 1,
        ]);

        $this->characterGoldBarDeposit
            ->shouldReceive('deposit')
            ->once()
            ->with($character->id, 2000)
            ->andReturn(0);

        $result = $this->characterCurrencyCacheService->useCache($character, $slot);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals(2000, $cacheItem->refresh()->cache_amount);
        $this->assertNotNull($slot->fresh());
    }
}
