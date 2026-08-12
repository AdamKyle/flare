<?php

namespace Tests\Unit\Game\Shop\Services;

use App\Flare\Models\Character;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Shop\Services\GemShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;

class GemShopServiceTest extends TestCase
{
    use CreateGem, RefreshDatabase;

    private ?Character $character;

    private ?GemShopService $shopService;

    protected function setUp(): void
    {
        parent::setUp();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $characterFactory->gemBagManagement()->assignGemToBag($this->createGem()->id);

        $this->character = $characterFactory->getCharacter();

        $this->shopService = resolve(GemShopService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->shopService = null;
    }

    public function test_cannot_sell_gem_you_do_not_have()
    {
        $result = $this->shopService->sellGem($this->character, 10);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Gem not found. Nothing to sell.', $result['message']);
    }

    public function test_sell_gem()
    {
        $gemSlot = $this->character->gemBag->gemSlots->first();

        $result = $this->shopService->sellGem($this->character, $gemSlot->id);

        $character = $this->character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEmpty($character->gemBag->gemSlots);
        $this->assertGreaterThan(0, $character->gold_dust);
        $this->assertGreaterThan(0, $character->shards);
        $this->assertGreaterThan(0, $character->copper_coins);
    }

    public function test_sell_gem_when_currency_capped()
    {

        $this->character->update([
            'copper_coins' => CurrencyLimit::MAX_COPPER,
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
        ]);

        $this->character = $this->character->refresh();

        $gemSlot = $this->character->gemBag->gemSlots->first();

        $result = $this->shopService->sellGem($this->character, $gemSlot->id);

        $character = $this->character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEmpty($character->gemBag->gemSlots);
        $this->assertEquals(CurrencyLimit::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertEquals(CurrencyLimit::MAX_SHARDS, $character->shards);
        $this->assertEquals(CurrencyLimit::MAX_COPPER, $character->copper_coins);
    }

    public function test_sell_all_gems()
    {
        $result = $this->shopService->sellAllGems($this->character);

        $character = $this->character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEmpty($character->gemBag->gemSlots);
        $this->assertGreaterThan(0, $character->gold_dust);
        $this->assertGreaterThan(0, $character->shards);
        $this->assertGreaterThan(0, $character->copper_coins);
    }

    public function test_sell_all_gems_when_currency_capped()
    {
        $this->character->update([
            'copper_coins' => CurrencyLimit::MAX_COPPER,
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
        ]);

        $this->character = $this->character->refresh();

        $result = $this->shopService->sellAllGems($this->character);

        $character = $this->character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEmpty($character->gemBag->gemSlots);
        $this->assertEquals(CurrencyLimit::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertEquals(CurrencyLimit::MAX_SHARDS, $character->shards);
        $this->assertEquals(CurrencyLimit::MAX_COPPER, $character->copper_coins);
    }
}
