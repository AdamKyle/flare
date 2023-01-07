<?php

namespace App\Game\Shop\Services;


use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Kingdom;
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

        // Initialize an array to store the contribution of each kingdom
        $contributions = [];

        // Calculate the total number of gold bars available
        $totalGoldBars = $kingdoms->sum('gold_bars');

        // Calculate the contribution of each kingdom
        foreach ($kingdoms as $kingdom) {
            // Calculate the contribution of this kingdom
            $contribution = floor($goldBarCost * $kingdom->gold_bars / $totalGoldBars);
            // Make sure the contribution does not exceed the available gold bars
            $contribution = min($contribution, $kingdom->gold_bars);

            // Update the contribution of this kingdom in the array
            $contributions[$kingdom->id] = $contribution;

            // Update the remaining cost
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
