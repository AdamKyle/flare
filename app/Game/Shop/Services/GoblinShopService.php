<?php

namespace App\Game\Shop\Services;

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

        $kingdoms = $character->kingdoms()
            ->whereRaw('(SELECT SUM(gold_bars) FROM kingdoms WHERE gold_bars > 0) >= ?', [$item->gold_bars_cost])
            ->groupBy('kingdoms.id', 'kingdoms.character_id', 'kingdoms.name')
            ->selectRaw('*, SUM(gold_bars) as gold_bars_sum')
            ->get();

        HandleGoldBarsAsACurrency::subtractCostFromKingdoms($kingdoms, $item->gold_bars_cost);

        $character->inventory->slots()->create([
            'character_inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
        ]);

        $characterGoldBars = $character->refresh()->kingdoms->sum('gold_bars');

        return $this->successResult([
            'message' => 'Purchased: '.$item->affix_name,
            'character_gold_bars' => $characterGoldBars,
        ]);
    }
}
