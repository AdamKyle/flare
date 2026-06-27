<?php

namespace App\Game\Shop\Services;

use App\Flare\Models\AlchemyBag;
use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Pagination\Pagination;
use App\Flare\Transformers\UsableItemTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use Facades\App\Game\Core\Handlers\HandleGoldBarsAsACurrency;

class GoblinShopService
{
    use ResponseBuilder;

    public function __construct(private readonly Pagination $pagination, private readonly UsableItemTransformer $usableItemTransformer) {}

    public function fetchItemsForShop(Character $character, int $perPage = 10, int $page = 1): array
    {
        $items = Item::whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->where('gold_bars_cost', '>', 0)
            ->orderBy('gold_bars_cost')
            ->get();

        return $this->pagination->buildPaginatedDate($items, $this->usableItemTransformer, $perPage, $page);
    }

    /**
     * Buy the item.
     */
    public function buyItem(Character $character, Item $item): array
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

        $characterGoldBars = $character->refresh()->kingdoms->sum('gold_bars');

        return $this->successResult([
            'message' => 'Purchased: '.$item->affix_name,
            'character_gold_bars' => $characterGoldBars,
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
