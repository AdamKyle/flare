<?php

namespace App\Game\Market\Services;

use App\Flare\Models\Item;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory as MarketHistoryListings;

class MarketSaleHistory
{
    /**
     * Get the market data for the item being listed.
     */
    public function getSaleInformationForItem(Item $item): array
    {
        $labels = MarketBoard::where('item_id', $item->id)->pluck('created_at')->toArray();
        $labels = $this->formatLabels($labels);
        $data = MarketBoard::where('item_id', $item->id)->pluck('listed_price')->toArray();

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get the history for a specific item.
     */
    public function getHistoricalListingData(): array
    {
        $labels = MarketHistoryListings::pluck('item_id')->toArray();
        $labels = $this->createLabelsFromItemIds($labels);
        $data = MarketHistoryListings::pluck('sold_for')->toArray();

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Create labels from the item id's
     */
    protected function createLabelsFromItemIds(array $ids): array
    {
        $items = Item::whereIn('id', $ids)->get();

        $labels = [];

        foreach ($items as $item) {
            $labels[] = $item->affix_name;
        }

        return $labels;
    }

    /**
     * Format labels for history.
     */
    protected function formatLabels(array $labels): array
    {
        foreach ($labels as $index => $label) {
            $labels[$index] = $label->timezone(config('app.timezone'))->format('D M Y H:m:s');
        }

        return $labels;
    }
}
