<?php

namespace Tests\Unit\Game\Shop\Services;

use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
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

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->character->gemBag()->create(['character_id' => $this->character->id]);

        $this->character->gemBag->gemSlots()->create([
            'gem_bag_id' => $this->character->gemBag->id,
            'gem_id' => $this->createGem()->id,
            'amount' => 1,
        ]);

        $this->character = $this->character->refresh();

        $this->shopService = resolve(GemShopService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->shopService = null;
    }

    public function testCannotSellGemYouDoNotHave()
    {
        $result = $this->shopService->sellGem($this->character, 10);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Gem not found. Nothing to sell.', $result['message']);
    }

    public function testSellGem()
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

    public function testSellGemWhenCurrencyCapped()
    {

        $this->character->update([
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $this->character = $this->character->refresh();

        $gemSlot = $this->character->gemBag->gemSlots->first();

        $result = $this->shopService->sellGem($this->character, $gemSlot->id);

        $character = $this->character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEmpty($character->gemBag->gemSlots);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertEquals(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
        $this->assertEquals(MaxCurrenciesValue::MAX_COPPER, $character->copper_coins);
    }

    public function testSellAllGems()
    {
        $result = $this->shopService->sellAllGems($this->character);

        $character = $this->character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEmpty($character->gemBag->gemSlots);
        $this->assertGreaterThan(0, $character->gold_dust);
        $this->assertGreaterThan(0, $character->shards);
        $this->assertGreaterThan(0, $character->copper_coins);
    }

    public function testSellAllGemsWhenCurrencyCapped()
    {
        $this->character->update([
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $this->character = $this->character->refresh();

        $result = $this->shopService->sellAllGems($this->character);

        $character = $this->character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEmpty($character->gemBag->gemSlots);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertEquals(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
        $this->assertEquals(MaxCurrenciesValue::MAX_COPPER, $character->copper_coins);
    }
}
