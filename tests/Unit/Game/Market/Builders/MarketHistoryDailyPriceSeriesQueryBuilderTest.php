<?php

namespace Tests\Unit\Game\Market\Builders;

use App\Game\Market\Builders\MarketHistoryDailyPriceSeriesQueryBuilder;
use App\Game\Market\Enums\MarketHistorySecondaryFilter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateMarketHistory;

class MarketHistoryDailyPriceSeriesQueryBuilderTest extends TestCase
{
    use CreateItem, CreateItemAffix, CreateMarketHistory, RefreshDatabase;

    private ?MarketHistoryDailyPriceSeriesQueryBuilder $query = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->query = resolve(MarketHistoryDailyPriceSeriesQueryBuilder::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->query = null;
    }

    public function test_to_recharts_returns_daily_series_for_type_with_missing_days_as_null(): void
    {
        $now = CarbonImmutable::parse('2026-01-16 12:00:00');

        $swordItem = $this->createItem([
            'type' => 'sword',
        ]);

        $daggerItem = $this->createItem([
            'type' => 'dagger',
        ]);

        $this->createMarketHistory([
            'item_id' => $swordItem->id,
            'sold_for' => 10,
            'created_at' => CarbonImmutable::parse('2026-01-12 10:00:00'),
        ]);

        $this->createMarketHistory([
            'item_id' => $swordItem->id,
            'sold_for' => 20,
            'created_at' => CarbonImmutable::parse('2026-01-14 10:00:00'),
        ]);

        $this->createMarketHistory([
            'item_id' => $swordItem->id,
            'sold_for' => 30,
            'created_at' => CarbonImmutable::parse('2026-01-14 18:00:00'),
        ]);

        $this->createMarketHistory([
            'item_id' => $daggerItem->id,
            'sold_for' => 999,
            'created_at' => CarbonImmutable::parse('2026-01-14 12:00:00'),
        ]);

        $series = $this->query
            ->setup($now, 7)
            ->forType('sword')
            ->toRecharts();

        $this->assertCount(7, $series);
        $this->assertEquals('2026-01-10', $series[0]['date']);
        $this->assertEquals('2026-01-16', $series[6]['date']);

        $seriesByDate = collect($series)->keyBy('date');

        $this->assertNull($seriesByDate['2026-01-10']['cost']);
        $this->assertNull($seriesByDate['2026-01-11']['cost']);
        $this->assertEquals(10.0, $seriesByDate['2026-01-12']['cost']);
        $this->assertNull($seriesByDate['2026-01-13']['cost']);
        $this->assertEquals(25.0, $seriesByDate['2026-01-14']['cost']);
        $this->assertNull($seriesByDate['2026-01-15']['cost']);
        $this->assertNull($seriesByDate['2026-01-16']['cost']);
    }

    public function test_add_filter_does_not_duplicate_and_clear_filters_resets(): void
    {
        $now = CarbonImmutable::parse('2026-01-16 12:00:00');

        $prefixAffix = $this->createItemAffix([
            'type' => 'prefix',
        ]);

        $singleEnchantSword = $this->createItem([
            'type' => 'sword',
            'item_prefix_id' => $prefixAffix->id,
            'item_suffix_id' => null,
        ]);

        $plainSword = $this->createItem([
            'type' => 'sword',
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ]);

        $this->createMarketHistory([
            'item_id' => $singleEnchantSword->id,
            'sold_for' => 10,
            'created_at' => CarbonImmutable::parse('2026-01-13 10:00:00'),
        ]);

        $this->createMarketHistory([
            'item_id' => $plainSword->id,
            'sold_for' => 100,
            'created_at' => CarbonImmutable::parse('2026-01-13 12:00:00'),
        ]);

        $filteredSeries = $this->query
            ->setup($now, 7)
            ->forType('sword')
            ->addFilter(MarketHistorySecondaryFilter::SingleEnchant)
            ->addFilter(MarketHistorySecondaryFilter::SingleEnchant)
            ->toRecharts();

        $filteredSeriesByDate = collect($filteredSeries)->keyBy('date');

        $this->assertEquals(10.0, $filteredSeriesByDate['2026-01-13']['cost']);

        $unfilteredSeries = $this->query
            ->clearFilters()
            ->toRecharts();

        $unfilteredSeriesByDate = collect($unfilteredSeries)->keyBy('date');

        $this->assertEquals(55.0, $unfilteredSeriesByDate['2026-01-13']['cost']);
    }

    public function test_double_enchant_filter_only_includes_double_enchants(): void
    {
        $now = CarbonImmutable::parse('2026-01-16 12:00:00');

        $prefixAffix = $this->createItemAffix([
            'type' => 'prefix',
        ]);

        $suffixAffix = $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $doubleEnchantSword = $this->createItem([
            'type' => 'sword',
            'item_prefix_id' => $prefixAffix->id,
            'item_suffix_id' => $suffixAffix->id,
        ]);

        $singleEnchantSword = $this->createItem([
            'type' => 'sword',
            'item_prefix_id' => $prefixAffix->id,
            'item_suffix_id' => null,
        ]);

        $this->createMarketHistory([
            'item_id' => $doubleEnchantSword->id,
            'sold_for' => 20,
            'created_at' => CarbonImmutable::parse('2026-01-14 10:00:00'),
        ]);

        $this->createMarketHistory([
            'item_id' => $singleEnchantSword->id,
            'sold_for' => 80,
            'created_at' => CarbonImmutable::parse('2026-01-14 12:00:00'),
        ]);

        $series = $this->query
            ->setup($now, 7)
            ->forType('sword')
            ->clearFilters()
            ->addFilter(MarketHistorySecondaryFilter::DoubleEnchant)
            ->toRecharts();

        $seriesByDate = collect($series)->keyBy('date');

        $this->assertEquals(20.0, $seriesByDate['2026-01-14']['cost']);
    }

    public function test_unique_filter_only_includes_random_affix_items(): void
    {
        $now = CarbonImmutable::parse('2026-01-16 12:00:00');

        $randomPrefix = $this->createItemAffix([
            'type' => 'prefix',
            'randomly_generated' => true,
        ]);

        $randomSuffix = $this->createItemAffix([
            'type' => 'suffix',
            'randomly_generated' => true,
        ]);

        $normalPrefix = $this->createItemAffix([
            'type' => 'prefix',
            'randomly_generated' => false,
        ]);

        $uniquePrefixSword = $this->createItem([
            'type' => 'sword',
            'item_prefix_id' => $randomPrefix->id,
            'item_suffix_id' => null,
        ]);

        $uniqueSuffixSword = $this->createItem([
            'type' => 'sword',
            'item_prefix_id' => null,
            'item_suffix_id' => $randomSuffix->id,
        ]);

        $notUniqueSword = $this->createItem([
            'type' => 'sword',
            'item_prefix_id' => $normalPrefix->id,
            'item_suffix_id' => null,
        ]);

        $this->createMarketHistory([
            'item_id' => $uniquePrefixSword->id,
            'sold_for' => 10,
            'created_at' => CarbonImmutable::parse('2026-01-15 09:00:00'),
        ]);

        $this->createMarketHistory([
            'item_id' => $uniqueSuffixSword->id,
            'sold_for' => 30,
            'created_at' => CarbonImmutable::parse('2026-01-15 10:00:00'),
        ]);

        $this->createMarketHistory([
            'item_id' => $notUniqueSword->id,
            'sold_for' => 100,
            'created_at' => CarbonImmutable::parse('2026-01-15 12:00:00'),
        ]);

        $series = $this->query
            ->setup($now, 7)
            ->forType('sword')
            ->clearFilters()
            ->addFilter(MarketHistorySecondaryFilter::Unique)
            ->toRecharts();

        $seriesByDate = collect($series)->keyBy('date');

        $this->assertEquals(20.0, $seriesByDate['2026-01-15']['cost']);
    }

    public function test_mythic_and_cosmic_filters_can_be_combined(): void
    {
        $now = CarbonImmutable::parse('2026-01-16 12:00:00');

        $mythicOnlySword = $this->createItem([
            'type' => 'sword',
            'is_mythic' => true,
            'is_cosmic' => false,
        ]);

        $mythicCosmicSword = $this->createItem([
            'type' => 'sword',
            'is_mythic' => true,
            'is_cosmic' => true,
        ]);

        $this->createMarketHistory([
            'item_id' => $mythicOnlySword->id,
            'sold_for' => 40,
            'created_at' => CarbonImmutable::parse('2026-01-16 10:00:00'),
        ]);

        $this->createMarketHistory([
            'item_id' => $mythicCosmicSword->id,
            'sold_for' => 100,
            'created_at' => CarbonImmutable::parse('2026-01-16 12:00:00'),
        ]);

        $series = $this->query
            ->setup($now, 7)
            ->forType('sword')
            ->clearFilters()
            ->addFilter(MarketHistorySecondaryFilter::Mythic)
            ->addFilter(MarketHistorySecondaryFilter::Cosmic)
            ->toRecharts();

        $seriesByDate = collect($series)->keyBy('date');

        $this->assertEquals(100.0, $seriesByDate['2026-01-16']['cost']);
    }

    public function test_for_item_id_filters_to_specific_item(): void
    {
        $now = CarbonImmutable::parse('2026-01-16 12:00:00');

        $firstSword = $this->createItem([
            'type' => 'sword',
        ]);

        $secondSword = $this->createItem([
            'type' => 'sword',
        ]);

        $this->createMarketHistory([
            'item_id' => $firstSword->id,
            'sold_for' => 10,
            'created_at' => CarbonImmutable::parse('2026-01-11 10:00:00'),
        ]);

        $this->createMarketHistory([
            'item_id' => $secondSword->id,
            'sold_for' => 30,
            'created_at' => CarbonImmutable::parse('2026-01-11 12:00:00'),
        ]);

        $series = $this->query
            ->setup($now, 7)
            ->forItemId($firstSword->id)
            ->toRecharts();

        $seriesByDate = collect($series)->keyBy('date');

        $this->assertEquals(10.0, $seriesByDate['2026-01-11']['cost']);
    }

    public function test_setup_with_zero_days_returns_single_day_series(): void
    {
        $now = CarbonImmutable::parse('2026-01-16 12:00:00');

        $sword = $this->createItem([
            'type' => 'sword',
        ]);

        $this->createMarketHistory([
            'item_id' => $sword->id,
            'sold_for' => 50,
            'created_at' => CarbonImmutable::parse('2026-01-16 23:59:59'),
        ]);

        $series = $this->query
            ->setup($now, 0)
            ->forType('sword')
            ->toRecharts();

        $this->assertCount(1, $series);
        $this->assertEquals('2026-01-16', $series[0]['date']);
        $this->assertEquals(50.0, $series[0]['cost']);
    }

    public function test_to_recharts_calls_setup_when_not_prepared(): void
    {
        $now = CarbonImmutable::now();

        $sword = $this->createItem([
            'type' => 'sword',
        ]);

        $this->createMarketHistory([
            'item_id' => $sword->id,
            'sold_for' => 77,
            'created_at' => $now,
        ]);

        $series = $this->query
            ->forType('sword')
            ->toRecharts();

        $this->assertCount(7, $series);
        $this->assertEquals($now->toDateString(), $series[6]['date']);
        $this->assertEquals(77.0, $series[6]['cost']);
    }
}
