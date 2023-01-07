<?php

namespace App\Game\Shop\Controllers;

use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Flare\Models\Kingdom;
use App\Game\Shop\Requests\ShopBuyMultipleValidation;
use App\Game\Shop\Requests\ShopPurchaseMultipleValidation;
use App\Game\Shop\Services\GoblinShopService;
use Cache;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;
use App\Http\Controllers\Controller;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Services\EquipItemService;
use App\Game\Core\Services\ComparisonService;
use App\Game\Shop\Jobs\PurchaseItemsJob;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Shop\Events\SellItemEvent;
use App\Game\Shop\Services\ShopService;
use App\Game\Shop\Requests\ShopReplaceItemValidation;


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
            ->whereRaw('(SELECT SUM(gold_bars) FROM kingdoms) >= ?', [$item->gold_bars_cost])
            ->havingRaw('SUM(gold_bars) >= ?', [$item->gold_bars_cost])
            ->groupBy('kingdoms.id', 'kingdoms.character_id', 'kingdoms.name')
            ->selectRaw('*, SUM(gold_bars) as gold_bars_sum')
            ->get();

        if ($kingdoms->isEmpty()) {
            return redirect()->back()->with('error', 'Not enough gold bars. Go slay monsters to stalk your treasury.');
        }

        $this->goblinShopService->buyItem($character, $item, $kingdoms);

        return redirect()->back()->with('success', 'Purchased: ' . $item->name . '. Cost has been taken from all kingdoms - split evenly - that could afford this item.
        This item will be in your usable section of your inventory.');
    }
}
