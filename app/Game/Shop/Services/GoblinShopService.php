<?php

namespace App\Game\Shop\Services;


use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Collection;

class GoblinShopService {

    /**
     * Buy the item.
     *
     * @param Character $character
     * @param Item $item
     * @param Collection $kingdoms
     * @return void
     */
    public function buyItem(Character $character, Item $item, Collection $kingdoms): void {
        $this->subtractCostFromKingdoms($kingdoms, $item->gold_bars_cost);

        $character->inventory->slots()->create([
            'character_inventory_id' => $character->inventory->id,
            'item_id'                => $item->id,
        ]);
    }

    /**
     * Subtract the cost from kingdoms gold bars.
     *
     * @param Collection $kingdoms
     * @param int $goldBarCost
     * @return void
     */
    protected function subtractCostFromKingdoms(Collection $kingdoms, int $goldBarCost): void {

        $contributions = [];

        $totalGoldBars = $kingdoms->sum('gold_bars');

        $kingdomWhoCanAbsorbCost = $kingdoms->where('gold_bars', '>=', $goldBarCost)->first();

        if (!is_null($kingdomWhoCanAbsorbCost)) {
            $newGoldBars = $kingdomWhoCanAbsorbCost->gold_bars - $goldBarCost;

            $kingdomWhoCanAbsorbCost->update([
                'gold_bars' => max($newGoldBars, 0),
            ]);

            return;
        }

        foreach ($kingdoms as $kingdom) {

            $contribution = floor($goldBarCost * $kingdom->gold_bars / $totalGoldBars);

            $contribution = min($contribution, $kingdom->gold_bars);

            $contributions[$kingdom->id] = $contribution;

            $goldBarCost -= $contribution;
            $totalGoldBars -= $kingdom->gold_bars;
        }

        foreach ($kingdoms as $kingdom) {
            $newAmount = $kingdom->gold_bars - $contributions[$kingdom->id];

            $kingdom->update([
                'gold_bars' => max($newAmount, 0),
            ]);
        }
    }
}
