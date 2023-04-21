<?php

namespace App\Game\Core\Handlers;

use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use Illuminate\Database\Eloquent\Collection;

class HandleGoldBarsAsACurrency {

    private UpdateKingdomHandler $updateKingdomHandler;

    public function __construct(UpdateKingdomHandler $updateKingdomHandler) {
        $this->updateKingdomHandler = $updateKingdomHandler;
    }

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

        $contributions = [];

        $totalGoldBars = $kingdoms->sum('gold_bars');

        $kingdomWhoCanAbsorbCost = $kingdoms->where('gold_bars', '>=', $goldBarCost)->first();

        if (!is_null($kingdomWhoCanAbsorbCost)) {
            $newGoldBars = $kingdomWhoCanAbsorbCost->gold_bars - $goldBarCost;

            $kingdomWhoCanAbsorbCost->update([
                'gold_bars' => max($newGoldBars, 0),
            ]);

            $this->updateKingdomHandler->refreshPlayersKingdoms($kingdoms->first()->character->refresh());

            return;
        }

        $kingdoms = $kingdoms->filter(function($kingdom) { return $kingdom->gold_bars > 0; });

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

        $this->updateKingdomHandler->refreshPlayersKingdoms($kingdoms->first()->character->refresh());
    }
}
