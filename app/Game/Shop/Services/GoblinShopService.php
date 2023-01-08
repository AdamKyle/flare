<?php

namespace App\Game\Shop\Services;


use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Collection;

class GoblinShopService {

    public function buyItem(Character $character, Item $item, Collection $kingdoms): void {
        $this->subtractCostFromKingdoms($kingdoms, $item->gold_bars_cost);

        $character->inventory->slots()->create([
            'character_inventory_id' => $character->inventory->id,
            'item_id'                => $item->id,
        ]);
    }

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

            if ($kingdom->gold_bars <= 0) {
                continue;
            }

            $contribution = floor($goldBarCost * $kingdom->gold_bars / $totalGoldBars);

            $contribution = min($contribution, $kingdom->gold_bars);

            $contributions[$kingdom->id] = $contribution;

            $goldBarCost -= $contribution;
            $totalGoldBars -= $kingdom->gold_bars;
        }

        $totalContributions = array_sum(array_values($contributions));
        $costRemaining      = $goldBarCost - $totalContributions;

        while ($costRemaining > 0) {
            foreach ($contributions as $kingdomId => $contribution) {

                $possibleContributionAddition = $contribution + 1;

                if ($kingdoms->where('id', $kingdomId)->first()->gold_bars >= $possibleContributionAddition) {
                    $contributions[$kingdomId] = $possibleContributionAddition;
                }
            }

            $costRemaining--;
        }

        foreach ($kingdoms as $kingdom) {
            $newAmount = $kingdom->gold_bars - $contributions[$kingdom->id];

            $kingdom->update([
                'gold_bars' => max($newAmount, 0),
            ]);
        }
    }
}
