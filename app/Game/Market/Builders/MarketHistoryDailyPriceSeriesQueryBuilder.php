<?php

namespace App\Game\Market\Builders;

use App\Flare\Models\MarketHistory;
use App\Game\Market\Enums\MarketHistorySecondaryFilter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MarketHistoryDailyPriceSeriesQueryBuilder
{
    private ?CarbonImmutable $startDate = null;

    private ?CarbonImmutable $endDate = null;

    private ?int $days = null;

    private ?string $itemType = null;

    private ?int $itemId = null;

    /**
     * @var array<int, MarketHistorySecondaryFilter>
     */
    private array $filters = [];

    public function setup(?CarbonImmutable $now = null, int $days = 7): self
    {
        $resolvedNow = $now ?? CarbonImmutable::now();

        $resolvedDays = max(1, $days);

        $this->days = $resolvedDays;
        $this->startDate = $resolvedNow->subDays($resolvedDays - 1)->startOfDay();
        $this->endDate = $resolvedNow->endOfDay();

        return $this;
    }

    public function forType(?string $itemType): self
    {
        $this->itemType = $itemType;

        return $this;
    }

    public function forItemId(?int $itemId): self
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function addFilter(MarketHistorySecondaryFilter $filter): self
    {
        if (! in_array($filter, $this->filters, true)) {
            $this->filters[] = $filter;
        }

        return $this;
    }

    public function clearFilters(): self
    {
        $this->filters = [];

        return $this;
    }

    /**
     * @return array<int, array{date: string, cost: float|null}>
     */
    public function toRecharts(): array
    {
        $rows = $this->dailyAverageRows();
        $costByDate = $rows->mapWithKeys(static function (object $row): array {
            return [$row->date => (float) $row->cost];
        });

        $series = [];

        for ($dayIndex = 0; $dayIndex < $this->days; $dayIndex++) {
            $date = $this->startDate->addDays($dayIndex)->toDateString();

            $series[] = [
                'date' => $date,
                'cost' => $costByDate[$date] ?? null,
            ];
        }

        return $series;
    }

    /**
     * @return Collection<int, object>
     */
    private function dailyAverageRows(): Collection
    {
        $query = $this->applyFilters($this->baseQuery());

        return $query
            ->selectRaw('DATE(mh.created_at) as date')
            ->selectRaw('AVG(mh.sold_for) as cost')
            ->groupBy(DB::raw('DATE(mh.created_at)'))
            ->orderBy('date')
            ->get();
    }

    private function baseQuery(): Builder
    {
        if (is_null($this->startDate) || is_null($this->endDate)) {
            $this->setup();
        }

        $query = MarketHistory::query()
            ->from('market_history as mh')
            ->join('items as i', 'i.id', '=', 'mh.item_id')
            ->leftJoin('item_affixes as p', 'p.id', '=', 'i.item_prefix_id')
            ->leftJoin('item_affixes as s', 's.id', '=', 'i.item_suffix_id')
            ->whereBetween('mh.created_at', [$this->startDate, $this->endDate]);

        if (! is_null($this->itemId)) {
            $query->where('i.id', $this->itemId);
        }

        if (! is_null($this->itemType)) {
            $query->where('i.type', $this->itemType);
        }

        return $query;
    }

    private function applyFilters(Builder $query): Builder
    {
        foreach ($this->filters as $filter) {
            $query = match ($filter) {
                MarketHistorySecondaryFilter::SingleEnchant => $this->applySingleEnchantFilter($query),
                MarketHistorySecondaryFilter::DoubleEnchant => $this->applyDoubleEnchantFilter($query),
                MarketHistorySecondaryFilter::Unique => $this->applyUniqueFilter($query),
                MarketHistorySecondaryFilter::Mythic => $this->applyMythicFilter($query),
                MarketHistorySecondaryFilter::Cosmic => $this->applyCosmicFilter($query),
            };
        }

        return $query;
    }

    private function applySingleEnchantFilter(Builder $query): Builder
    {
        return $query->where(static function (Builder $innerQuery): void {
            $innerQuery
                ->where(static function (Builder $left): void {
                    $left->whereNotNull('i.item_prefix_id')->whereNull('i.item_suffix_id');
                })
                ->orWhere(static function (Builder $right): void {
                    $right->whereNull('i.item_prefix_id')->whereNotNull('i.item_suffix_id');
                });
        });
    }

    private function applyDoubleEnchantFilter(Builder $query): Builder
    {
        return $query
            ->whereNotNull('i.item_prefix_id')
            ->whereNotNull('i.item_suffix_id');
    }

    private function applyUniqueFilter(Builder $query): Builder
    {
        return $query->where(static function (Builder $innerQuery): void {
            $innerQuery
                ->where('p.randomly_generated', true)
                ->orWhere('s.randomly_generated', true);
        });
    }

    private function applyMythicFilter(Builder $query): Builder
    {
        return $query->where('i.is_mythic', true);
    }

    private function applyCosmicFilter(Builder $query): Builder
    {
        return $query->where('i.is_cosmic', true);
    }
}
