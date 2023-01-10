<?php

namespace App\Game\Shop\Controllers;

use App\Http\Controllers\Controller;
use App\Game\Shop\Services\GoblinShopService;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;

class GoblinShopController extends Controller {

    /**
     * @var GoblinShopService $goblinShopService
     */
    private GoblinShopService $goblinShopService;

    /**
     * @param GoblinShopService $goblinShopService
     */
    public function __construct(GoblinShopService $goblinShopService) {
        $this->goblinShopService = $goblinShopService;
    }

    public function listItems(Character $character) {

        $location = Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->first();

        return view('game.shop.goblin-shop.buy', [
            'isLocation' => !is_null($location),
            'goldBars'   => $character->kingdoms->sum('gold_bars'),
            'character'  => $character,
        ]);
    }

    public function buyItem(Character $character, Item $item) {

        if ($character->isInventoryFull()) {
            return redirect()->back()->with('error', 'Your inventory is full. Cannot buy item.');
        }

        $kingdoms = $character->kingdoms()
            ->whereRaw('(SELECT SUM(gold_bars) FROM kingdoms WHERE gold_bars > 0) >= ?', [$item->gold_bars_cost])
            ->groupBy('kingdoms.id', 'kingdoms.character_id', 'kingdoms.name')
            ->selectRaw('*, SUM(gold_bars) as gold_bars_sum')
            ->get();

        if ($kingdoms->sum('gold_bars_sum') < $item->gold_bars_cost) {
            return redirect()->back()->with('error', 'Not enough gold bars. Go slay monsters to stalk your treasury.');
        }

        $this->goblinShopService->buyItem($character, $item, $kingdoms);

        return redirect()->back()->with('success', 'Purchased: ' . $item->name . '. Cost has been taken from all kingdoms - split evenly - that could afford this item.
        This item will be in your usable section of your inventory.');
    }
}
