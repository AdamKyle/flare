<?php

namespace Tests\Unit\Game\Market\Builders;

use App\Game\Market\Builders\MarketHistoryDailyPriceSeriesQueryBuilder;
use App\Game\Market\Enums\MarketHistorySecondaryFilter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateMarketHistory;

class MarketHistoryDailyPriceSeriesQueryBuilderTest extends TestCase
{
    use CreateItem, CreateItemAffix, CreateMarketHistory, RefreshDatabase;

    private ?MarketHistoryDailyPriceSeriesQueryBuilder $builder = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->builder = resolve(MarketHistoryDailyPriceSeriesQueryBuilder::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->builder = null;
    }

    public function test_fetchDataSet_throws_when_setup_not_called(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Call setup() before building the query.');

        $this->builder->fetchDataSet();
    }

    public function test_fetchDataSet_returns_empty_array_when_base_query_has_no_results(): void
    {
        $now = CarbonImmutable::now();

        $data = $this->builder
            ->setup('weapon', $now, 30)
            ->fetchDataSet()
            ->toArray();

        $this->assertSame([], $data);
    }

    public function test_fetchDataSet_returns_base_item_name_when_item_has_no_enchants(): void
    {
        $now = CarbonImmutable::now();

        $item = $this->createItem([
            'type' => 'weapon',
            'name' => 'Plain Sword',
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ]);

        $this->createMarketHistory([
            'item_id' => $item->id,
            'sold_for' => 55,
            'created_at' => $now->subDays(1),
        ]);

        $data = $this->builder
            ->setup('weapon', $now, 30)
            ->fetchDataSet()
            ->toArray();

        $this->assertCount(1, $data);
        $this->assertSame(55, $data[0]['cost']);
        $this->assertSame('Plain Sword', $data[0]['affix_name']);
    }

    public function test_fetchDataSet_returns_each_sale_cost_in_order_and_includes_affix_name(): void
    {
        $now = CarbonImmutable::now();

        $prefixId = $this->createItemAffix([
            'name' => 'Brutal',
            'type' => 'prefix',
            'randomly_generated' => false,
        ]);

        $suffixId = $this->createItemAffix([
            'name' => 'Doom',
            'type' => 'suffix',
            'randomly_generated' => false,
        ]);

        $item = $this->createItem([
            'type' => 'weapon',
            'name' => 'Sword',
            'item_prefix_id' => $prefixId,
            'item_suffix_id' => $suffixId,
        ]);

        $this->createMarketHistory([
            'item_id' => $item->id,
            'sold_for' => 10,
            'created_at' => $now->subDays(3),
        ]);

        $this->createMarketHistory([
            'item_id' => $item->id,
            'sold_for' => 15,
            'created_at' => $now->subDays(2),
        ]);

        $this->createMarketHistory([
            'item_id' => $item->id,
            'sold_for' => 20,
            'created_at' => $now->subDays(1),
        ]);

        $data = $this->builder
            ->setup('weapon', $now, 30)
            ->fetchDataSet()
            ->toArray();

        $this->assertCount(3, $data);

        $this->assertSame(10, $data[0]['cost']);
        $this->assertSame('*Brutal* Sword *Doom*', $data[0]['affix_name']);

        $this->assertSame(15, $data[1]['cost']);
        $this->assertSame('*Brutal* Sword *Doom*', $data[1]['affix_name']);

        $this->assertSame(20, $data[2]['cost']);
        $this->assertSame('*Brutal* Sword *Doom*', $data[2]['affix_name']);
    }

    public function test_single_enchant_filter_returns_only_single_enchants_and_returns_empty_when_none_match(): void
    {
        $now = CarbonImmutable::now();

        $prefixId = $this->createItemAffix([
            'name' => 'Brutal',
            'type' => 'prefix',
            'randomly_generated' => false,
        ]);

        $suffixId = $this->createItemAffix([
            'name' => 'Doom',
            'type' => 'suffix',
            'randomly_generated' => false,
        ]);

        $prefixOnly = $this->createItem([
            'type' => 'weapon',
            'name' => 'Sword',
            'item_prefix_id' => $prefixId,
            'item_suffix_id' => null,
        ]);

        $suffixOnly = $this->createItem([
            'type' => 'weapon',
            'name' => 'Axe',
            'item_prefix_id' => null,
            'item_suffix_id' => $suffixId,
        ]);

        $double = $this->createItem([
            'type' => 'weapon',
            'name' => 'Mace',
            'item_prefix_id' => $prefixId,
            'item_suffix_id' => $suffixId,
        ]);

        $this->createMarketHistory([
            'item_id' => $prefixOnly->id,
            'sold_for' => 11,
            'created_at' => $now->subDays(3),
        ]);

        $this->createMarketHistory([
            'item_id' => $double->id,
            'sold_for' => 99,
            'created_at' => $now->subDays(2),
        ]);

        $this->createMarketHistory([
            'item_id' => $suffixOnly->id,
            'sold_for' => 22,
            'created_at' => $now->subDays(1),
        ]);

        $singleData = $this->builder
            ->setup('weapon', $now, 30)
            ->addFilter(MarketHistorySecondaryFilter::SingleEnchant)
            ->fetchDataSet()
            ->toArray();

        $this->assertCount(2, $singleData);

        $this->assertSame(11, $singleData[0]['cost']);
        $this->assertSame('*Brutal* Sword', $singleData[0]['affix_name']);

        $this->assertSame(22, $singleData[1]['cost']);
        $this->assertSame('Axe *Doom*', $singleData[1]['affix_name']);

        $armorDouble = $this->createItem([
            'type' => 'armor',
            'name' => 'Armor',
            'item_prefix_id' => $prefixId,
            'item_suffix_id' => $suffixId,
        ]);

        $this->createMarketHistory([
            'item_id' => $armorDouble->id,
            'sold_for' => 77,
            'created_at' => $now->subDays(1),
        ]);

        $noneData = $this->builder
            ->clearFilters()
            ->setup('armor', $now, 30)
            ->addFilter(MarketHistorySecondaryFilter::SingleEnchant)
            ->fetchDataSet()
            ->toArray();

        $this->assertSame([], $noneData);
    }

    public function test_double_enchant_filter_returns_only_double_enchants_and_returns_empty_when_none_match(): void
    {
        $now = CarbonImmutable::now();

        $prefixId = $this->createItemAffix([
            'name' => 'Brutal',
            'type' => 'prefix',
            'randomly_generated' => false,
        ]);

        $suffixId = $this->createItemAffix([
            'name' => 'Doom',
            'type' => 'suffix',
            'randomly_generated' => false,
        ]);

        $double = $this->createItem([
            'type' => 'weapon',
            'name' => 'Sword',
            'item_prefix_id' => $prefixId,
            'item_suffix_id' => $suffixId,
        ]);

        $single = $this->createItem([
            'type' => 'weapon',
            'name' => 'Axe',
            'item_prefix_id' => $prefixId,
            'item_suffix_id' => null,
        ]);

        $this->createMarketHistory([
            'item_id' => $single->id,
            'sold_for' => 10,
            'created_at' => $now->subDays(2),
        ]);

        $this->createMarketHistory([
            'item_id' => $double->id,
            'sold_for' => 20,
            'created_at' => $now->subDays(1),
        ]);

        $doubleData = $this->builder
            ->setup('weapon', $now, 30)
            ->addFilter(MarketHistorySecondaryFilter::DoubleEnchant)
            ->fetchDataSet()
            ->toArray();

        $this->assertCount(1, $doubleData);
        $this->assertSame(20, $doubleData[0]['cost']);
        $this->assertSame('*Brutal* Sword *Doom*', $doubleData[0]['affix_name']);

        $armorSingle = $this->createItem([
            'type' => 'armor',
            'name' => 'Armor',
            'item_prefix_id' => $prefixId,
            'item_suffix_id' => null,
        ]);

        $this->createMarketHistory([
            'item_id' => $armorSingle->id,
            'sold_for' => 33,
            'created_at' => $now->subDays(1),
        ]);

        $noneData = $this->builder
            ->clearFilters()
            ->setup('armor', $now, 30)
            ->addFilter(MarketHistorySecondaryFilter::DoubleEnchant)
            ->fetchDataSet()
            ->toArray();

        $this->assertSame([], $noneData);
    }

    public function test_unique_filter_returns_only_unique_items_and_returns_empty_when_none_match(): void
    {
        $now = CarbonImmutable::now();

        $uniquePrefixId = $this->createItemAffix([
            'name' => 'Unique',
            'type' => 'prefix',
            'randomly_generated' => true,
        ]);

        $normalPrefixId = $this->createItemAffix([
            'name' => 'Normal',
            'type' => 'prefix',
            'randomly_generated' => false,
        ]);

        $uniqueItem = $this->createItem([
            'type' => 'weapon',
            'name' => 'Sword',
            'item_prefix_id' => $uniquePrefixId,
            'item_suffix_id' => null,
        ]);

        $normalItem = $this->createItem([
            'type' => 'weapon',
            'name' => 'Axe',
            'item_prefix_id' => $normalPrefixId,
            'item_suffix_id' => null,
        ]);

        $this->createMarketHistory([
            'item_id' => $normalItem->id,
            'sold_for' => 10,
            'created_at' => $now->subDays(2),
        ]);

        $this->createMarketHistory([
            'item_id' => $uniqueItem->id,
            'sold_for' => 20,
            'created_at' => $now->subDays(1),
        ]);

        $uniqueData = $this->builder
            ->setup('weapon', $now, 30)
            ->addFilter(MarketHistorySecondaryFilter::Unique)
            ->fetchDataSet()
            ->toArray();

        $this->assertCount(1, $uniqueData);
        $this->assertSame(20, $uniqueData[0]['cost']);
        $this->assertSame('*Unique* Sword', $uniqueData[0]['affix_name']);

        $armorNormal = $this->createItem([
            'type' => 'armor',
            'name' => 'Armor',
            'item_prefix_id' => $normalPrefixId,
            'item_suffix_id' => null,
        ]);

        $this->createMarketHistory([
            'item_id' => $armorNormal->id,
            'sold_for' => 33,
            'created_at' => $now->subDays(1),
        ]);

        $noneData = $this->builder
            ->clearFilters()
            ->setup('armor', $now, 30)
            ->addFilter(MarketHistorySecondaryFilter::Unique)
            ->fetchDataSet()
            ->toArray();

        $this->assertSame([], $noneData);
    }

    public function test_mythic_filter_returns_only_mythic_items_and_returns_empty_when_none_match(): void
    {
        $now = CarbonImmutable::now();

        $mythic = $this->createItem([
            'type' => 'weapon',
            'name' => 'Mythic',
            'is_mythic' => true,
            'is_cosmic' => false,
        ]);

        $normal = $this->createItem([
            'type' => 'weapon',
            'name' => 'Normal',
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);

        $this->createMarketHistory([
            'item_id' => $normal->id,
            'sold_for' => 10,
            'created_at' => $now->subDays(2),
        ]);

        $this->createMarketHistory([
            'item_id' => $mythic->id,
            'sold_for' => 20,
            'created_at' => $now->subDays(1),
        ]);

        $mythicData = $this->builder
            ->setup('weapon', $now, 30)
            ->addFilter(MarketHistorySecondaryFilter::Mythic)
            ->fetchDataSet()
            ->toArray();

        $this->assertCount(1, $mythicData);
        $this->assertSame(20, $mythicData[0]['cost']);

        $armorNormal = $this->createItem([
            'type' => 'armor',
            'name' => 'Armor',
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);

        $this->createMarketHistory([
            'item_id' => $armorNormal->id,
            'sold_for' => 33,
            'created_at' => $now->subDays(1),
        ]);

        $noneData = $this->builder
            ->clearFilters()
            ->setup('armor', $now, 30)
            ->addFilter(MarketHistorySecondaryFilter::Mythic)
            ->fetchDataSet()
            ->toArray();

        $this->assertSame([], $noneData);
    }

    public function test_cosmic_filter_returns_only_cosmic_items_and_returns_empty_when_none_match(): void
    {
        $now = CarbonImmutable::now();

        $cosmic = $this->createItem([
            'type' => 'weapon',
            'name' => 'Cosmic',
            'is_mythic' => false,
            'is_cosmic' => true,
        ]);

        $normal = $this->createItem([
            'type' => 'weapon',
            'name' => 'Normal',
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);

        $this->createMarketHistory([
            'item_id' => $normal->id,
            'sold_for' => 10,
            'created_at' => $now->subDays(2),
        ]);

        $this->createMarketHistory([
            'item_id' => $cosmic->id,
            'sold_for' => 20,
            'created_at' => $now->subDays(1),
        ]);

        $cosmicData = $this->builder
            ->setup('weapon', $now, 30)
            ->addFilter(MarketHistorySecondaryFilter::Cosmic)
            ->fetchDataSet()
            ->toArray();

        $this->assertCount(1, $cosmicData);
        $this->assertSame(20, $cosmicData[0]['cost']);

        $armorNormal = $this->createItem([
            'type' => 'armor',
            'name' => 'Armor',
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);

        $this->createMarketHistory([
            'item_id' => $armorNormal->id,
            'sold_for' => 33,
            'created_at' => $now->subDays(1),
        ]);

        $noneData = $this->builder
            ->clearFilters()
            ->setup('armor', $now, 30)
            ->addFilter(MarketHistorySecondaryFilter::Cosmic)
            ->fetchDataSet()
            ->toArray();

        $this->assertSame([], $noneData);
    }

    public function test_clearFilters_restores_unfiltered_results(): void
    {
        $now = CarbonImmutable::now();

        $cosmic = $this->createItem([
            'type' => 'weapon',
            'name' => 'Cosmic',
            'is_mythic' => false,
            'is_cosmic' => true,
        ]);

        $normal = $this->createItem([
            'type' => 'weapon',
            'name' => 'Normal',
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);

        $this->createMarketHistory([
            'item_id' => $normal->id,
            'sold_for' => 10,
            'created_at' => $now->subDays(2),
        ]);

        $this->createMarketHistory([
            'item_id' => $cosmic->id,
            'sold_for' => 20,
            'created_at' => $now->subDays(1),
        ]);

        $filtered = $this->builder
            ->setup('weapon', $now, 30)
            ->addFilter(MarketHistorySecondaryFilter::Cosmic)
            ->fetchDataSet()
            ->toArray();

        $this->assertCount(1, $filtered);

        $all = $this->builder
            ->clearFilters()
            ->setup('weapon', $now, 30)
            ->fetchDataSet()
            ->toArray();

        $this->assertCount(2, $all);
    }
}
