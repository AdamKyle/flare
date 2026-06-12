<?php

namespace App\Game\Shop\Services;

use App\Flare\Models\AlchemyBag;
use App\Flare\Models\AlchemyBagSlot;
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

        if ($item->type === 'alchemy') {
            $this->addToAlchemyBag($character, $item);

            return;
        }

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
        ]);
    }

    /**
     * Can the character buy this alchemy item?
     */
    public function canBuyAlchemyItem(Character $character): bool
    {
        return $character->canAddToAlchemyBag(1);
    }

    private function addToAlchemyBag(Character $character, Item $item): void
    {
        if (! $character->canAddToAlchemyBag(1)) {
            return;
        }

        $alchemyBag = AlchemyBag::firstOrCreate(['character_id' => $character->id]);

        $existingSlot = AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)
            ->where('item_id', $item->id)
            ->first();

        if (! is_null($existingSlot)) {
            $existingSlot->update(['amount' => $existingSlot->amount + 1]);

            return;
        }

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);
    }
}
