<?php

namespace Tests\Unit\Game\Gems\Progression\Services;

use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Gems\Progression\Services\GemScrollGenerator;
use App\Game\Gems\Progression\Values\GemScrollCurrencyType;
use App\Game\Gems\Progression\Values\GemScrollTierRanges;
use App\Game\Gems\Progression\Values\GemScrollType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GemScrollGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_tier_one_xp_scroll_uses_the_one_hundred_to_one_hundred_ninety_nine_range(): void
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 3)->once()->andReturn(1);
            $mock->shouldReceive('numberBetween')->with(500, 1500)->once()->andReturn(1000);
        });

        $gemScrollGenerator = new GemScrollGenerator($randomNumberGenerator, new GemScrollTierRanges);

        $item = $gemScrollGenerator->generateForPersonalLevel(150);

        $this->assertSame(GemScrollType::XP, $item->gem_scroll_type);
        $this->assertEqualsWithDelta(0.10, $item->gem_scroll_bonus, 0.0000001);
        $this->assertSame(120, $item->lasts_for);
        $this->assertTrue($item->randomly_generated);
        $this->assertTrue($item->usable);
        $this->assertFalse($item->can_craft);
        $this->assertFalse($item->market_sellable);
        $this->assertFalse($item->can_drop);
        $this->assertSame('alchemy', $item->type);
        $this->assertNull($item->gem_scroll_currency_type);
        $this->assertNull($item->gem_scroll_socket_chance);
        $this->assertNull($item->gem_scroll_pre_gem_chance);
    }

    public function test_tier_three_currency_scroll_uses_the_three_hundred_to_three_hundred_ninety_nine_range(): void
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 3)->once()->andReturn(2);
            $mock->shouldReceive('numberBetween')->with(3000, 4500)->once()->andReturn(4000);
            $mock->shouldReceive('numberBetween')->with(1, 4)->once()->andReturn(3);
        });

        $gemScrollGenerator = new GemScrollGenerator($randomNumberGenerator, new GemScrollTierRanges);

        $item = $gemScrollGenerator->generateForPersonalLevel(350);

        $this->assertSame(GemScrollType::CURRENCY, $item->gem_scroll_type);
        $this->assertEqualsWithDelta(0.40, $item->gem_scroll_bonus, 0.0000001);
        $this->assertSame(GemScrollCurrencyType::GOLD_DUST, $item->gem_scroll_currency_type);
        $this->assertSame(240, $item->lasts_for);
        $this->assertNull($item->gem_scroll_socket_chance);
        $this->assertNull($item->gem_scroll_pre_gem_chance);
    }

    public function test_tier_six_item_scroll_uses_the_six_hundred_plus_range(): void
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 3)->once()->andReturn(3);
            $mock->shouldReceive('numberBetween')->with(600, 800)->once()->andReturn(700);
            $mock->shouldReceive('numberBetween')->with(1000, 1500)->once()->andReturn(1200);
            $mock->shouldReceive('numberBetween')->with(700, 1000)->once()->andReturn(800);
        });

        $gemScrollGenerator = new GemScrollGenerator($randomNumberGenerator, new GemScrollTierRanges);

        $item = $gemScrollGenerator->generateForPersonalLevel(650);

        $this->assertSame(GemScrollType::ITEM, $item->gem_scroll_type);
        $this->assertEqualsWithDelta(0.07, $item->gem_scroll_bonus, 0.0000001);
        $this->assertEqualsWithDelta(0.12, $item->gem_scroll_socket_chance, 0.0000001);
        $this->assertEqualsWithDelta(0.08, $item->gem_scroll_pre_gem_chance, 0.0000001);
        $this->assertSame(480, $item->lasts_for);
        $this->assertNull($item->gem_scroll_currency_type);
    }

    public function test_currency_scroll_targets_exactly_one_of_the_four_typed_currencies(): void
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 3)->once()->andReturn(2);
            $mock->shouldReceive('numberBetween')->with(500, 1500)->once()->andReturn(500);
            $mock->shouldReceive('numberBetween')->with(1, 4)->once()->andReturn(2);
        });

        $gemScrollGenerator = new GemScrollGenerator($randomNumberGenerator, new GemScrollTierRanges);

        $item = $gemScrollGenerator->generateForPersonalLevel(150);

        $this->assertSame(GemScrollCurrencyType::COPPER_COINS, $item->gem_scroll_currency_type);
    }
}
