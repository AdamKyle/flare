<?php

namespace App\Game\Core\Handlers;

use Illuminate\Database\Eloquent\Collection;

class HandleGoldBarsAsACurrency {

    /**
     * Can afford the gold bars cost.
     *
     * @param Collection $kingdoms
     * @param int $cost
     * @return bool
     */
    public function hasTheGoldBars(Collection $kingdoms, int $cost): bool {
        if ($kingdoms->sum('gold_bars') < $cost) {
            return false;
        }

        return true;
    }

    /**
     * Subtract the cost from kingdoms gold bars.
     *
     * @param Collection $kingdoms
     * @param int $goldBarCost
     * @return void
     */
    public function subtractCostFromKingdoms(Collection $kingdoms, int $goldBarCost): void {

        $totalGoldBars = $kingdoms->sum('gold_bars');

        $kingdomWhoCanAbsorbCost = $kingdoms->where('gold_bars', '>=', $goldBarCost)->first();

        if (!is_null($kingdomWhoCanAbsorbCost)) {
            $newGoldBars = $kingdomWhoCanAbsorbCost->gold_bars - $goldBarCost;

            $kingdomWhoCanAbsorbCost->update([
                'gold_bars' => max($newGoldBars, 0),
            ]);

            return;
        }

        $kingdoms->each(function ($kingdom) use (&$goldBarCost, &$totalGoldBars) {
            if ($kingdom->gold_bars > 0) {
                $contribution = min(floor($goldBarCost * $kingdom->gold_bars / $totalGoldBars), $kingdom->gold_bars);
                $goldBarCost -= $contribution;
                $totalGoldBars -= $kingdom->gold_bars;
                $newAmount = max($kingdom->gold_bars - $contribution, 0);
                $kingdom->update(['gold_bars' => $newAmount]);
            }
        });
    }
}
