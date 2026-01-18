<?php

namespace App\Game\Market\Builders;

use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\MarketHistory;
use App\Game\Market\Enums\MarketHistorySecondaryFilter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use LogicException;

class MarketHistoryDailyPriceSeriesQueryBuilder
{
    private ?CarbonImmutable $startDate = null;

    private ?CarbonImmutable $endDate = null;

    private ?string $itemType = null;

    private ?MarketHistorySecondaryFilter $filter = null;

    /**
     * Configure the builder for a single item type, constrained to [$now - $days, $now].
     */
    public function setup(string $type, ?CarbonImmutable $now = null, int $days = 7): self
    {
        $this->itemType = $type;

        $resolvedNow = $now ?? CarbonImmutable::now();

        $resolvedDays = max(0, $days);

        $this->startDate = $resolvedNow->subDays($resolvedDays)->startOfDay();
        $this->endDate = $resolvedNow->endOfDay();

        return $this;
    }

    /**
     * Build the base query (type + timeframe + optional filter), ordered chronologically.
     *
     * @throws LogicException
     */
    public function baseQuery(): Builder
    {
        if ($this->startDate === null || $this->endDate === null) {
            throw new LogicException('Call setup() before building the query.');
        }

        $marketHistoryTable = (new MarketHistory())->getTable();
        $itemsTable = (new Item())->getTable();
        $affixesTable = (new ItemAffix())->getTable();

        $query = MarketHistory::query()
            ->from($marketHistoryTable)
            ->join($itemsTable, $itemsTable.'.id', '=', $marketHistoryTable.'.item_id')
            ->leftJoin($affixesTable.' as item_prefix', 'item_prefix.id', '=', $itemsTable.'.item_prefix_id')
            ->leftJoin($affixesTable.' as item_suffix', 'item_suffix.id', '=', $itemsTable.'.item_suffix_id')
            ->where($itemsTable.'.type', $this->itemType)
            ->whereBetween($marketHistoryTable.'.created_at', [$this->startDate, $this->endDate])
            ->orderBy($marketHistoryTable.'.created_at');

        $this->applyFilters($query, $itemsTable);

        return $query->select([
            $marketHistoryTable.'.sold_for as cost',
            $marketHistoryTable.'.created_at as sold_when',
            $itemsTable.'.name as item_name',
            'item_prefix.name as prefix_name',
            'item_suffix.name as suffix_name',
        ]);
    }

    public function clearFilters(): self
    {
        $this->filter = null;

        return $this;
    }

    public function addFilter(MarketHistorySecondaryFilter $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Fetch the dataset in the shape:
     * [
     *   ['cost' => int, 'affix_name' => string, 'sold_when' => string],
     *   ...
     * ]
     */
    public function fetchDataSet(): Collection
    {
        return $this->baseQuery()
            ->get()
            ->map(function (MarketHistory $marketHistory): array {
                $itemName = (string) $marketHistory->getAttribute('item_name');
                $prefixName = $marketHistory->getAttribute('prefix_name');
                $suffixName = $marketHistory->getAttribute('suffix_name');

                return [
                    'cost' => (int) $marketHistory->getAttribute('cost'),
                    'affix_name' => $this->formatAffixName(
                        $itemName,
                        is_string($prefixName) ? $prefixName : null,
                        is_string($suffixName) ? $suffixName : null,
                    ),
                    'sold_when' => (string) $marketHistory->getAttribute('sold_when'),
                ];
            })
            ->values();
    }

    private function applyFilters(Builder $query, string $itemsTable): void
    {
        if ($this->filter === null) {
            return;
        }

        match ($this->filter) {
            MarketHistorySecondaryFilter::SingleEnchant => $query->where(function (Builder $nestedQuery) use ($itemsTable): void {
                $nestedQuery
                    ->where(function (Builder $innerQuery) use ($itemsTable): void {
                        $innerQuery
                            ->whereNotNull($itemsTable.'.item_prefix_id')
                            ->whereNull($itemsTable.'.item_suffix_id');
                    })
                    ->orWhere(function (Builder $innerQuery) use ($itemsTable): void {
                        $innerQuery
                            ->whereNull($itemsTable.'.item_prefix_id')
                            ->whereNotNull($itemsTable.'.item_suffix_id');
                    });
            }),
            MarketHistorySecondaryFilter::DoubleEnchant => $query
                ->whereNotNull($itemsTable.'.item_prefix_id')
                ->whereNotNull($itemsTable.'.item_suffix_id'),
            MarketHistorySecondaryFilter::Unique => $query->where(function (Builder $nestedQuery): void {
                $nestedQuery
                    ->where('item_prefix.randomly_generated', true)
                    ->orWhere('item_suffix.randomly_generated', true);
            }),
            MarketHistorySecondaryFilter::Mythic => $query->where($itemsTable.'.is_mythic', true),
            MarketHistorySecondaryFilter::Cosmic => $query->where($itemsTable.'.is_cosmic', true),
        };
    }

    private function formatAffixName(string $itemName, ?string $prefixName, ?string $suffixName): string
    {
        $result = '';

        if ($prefixName !== null && $prefixName !== '') {
            $result = '*'.$prefixName.'* '.$itemName;
        }

        if ($suffixName !== null && $suffixName !== '') {
            $result .= $result !== '' ? ' *'.$suffixName.'*' : $itemName.' *'.$suffixName.'*';
        }

        return $result === '' ? $itemName : $result;
    }
}
