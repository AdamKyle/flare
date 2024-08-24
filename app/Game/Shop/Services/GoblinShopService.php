<?php

namespace App\Game\Shop\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Facades\App\Game\Core\Handlers\HandleGoldBarsAsACurrency;
use Illuminate\Database\Eloquent\Collection;

class GoblinShopService
{
    /**
     * Buy the item.
     */
    public function buyItem(Character $character, Item $item, Collection $kingdoms): void
    {

        HandleGoldBarsAsACurrency::subtractCostFromKingdoms($kingdoms, $item->gold_bars_cost);

        $character->inventory->slots()->create([
            'character_inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
        ]);
    }
}
