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
        $count = $kingdoms->count();

        $costPerKingdom = floor($goldBarCost / $count);
        $remainingBars  = $goldBarCost % $count;

        foreach ($kingdoms as $kingdom) {
            $newAmount          = $kingdom->gold_bars - $costPerKingdom;
            $kingdom->gold_bars = max($newAmount, 0);
            $kingdom->save();

            $remainingBars--;

            if ($remainingBars == 0) {
                break;
            }
        }
    }
}
