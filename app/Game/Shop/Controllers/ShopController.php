<?php

namespace App\Game\Shop\Controllers;

use App\Game\CharacterInventory\Services\ComparisonService;
use App\Game\Shop\Requests\ShopBuyMultipleOfItem;
use Cache;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Shop\Requests\ShopPurchaseMultipleValidation;
use App\Game\CharacterInventory\Services\EquipItemService;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Shop\Services\ShopService;
use App\Game\Shop\Requests\ShopReplaceItemValidation;


class ShopController extends Controller {

    private EquipItemService $equipItemService;

    private BuildCharacterAttackTypes $buildCharacterAttackTypes;

    private CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer;

    private ShopService $shopService;

    private Manager $manager;

    public function __construct(
        EquipItemService $equipItemService,
        BuildCharacterAttackTypes $buildCharacterAttackTypes,
        CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer,
        ShopService $shopService,
        Manager $manager
    ) {
        $this->middleware('auth');
        $this->middleware('is.character.dead');

        $this->equipItemService                       = $equipItemService;
        $this->buildCharacterAttackTypes              = $buildCharacterAttackTypes;
        $this->characterSheetBaseInfoTransformer      = $characterSheetBaseInfoTransformer;
        $this->shopService                            = $shopService;
        $this->manager                                = $manager;
    }

    public function shopBuy(Character $character) {

        $location = Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->first();

        return view('game.shop.buy', [
            'isLocation' => !is_null($location),
            'gold'       => $character->gold,
            'character'  => $character,
        ]);
    }

    public function shopSell(Character $character) {

        $location = Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->first();

        return view('game.shop.sell', [
            'isLocation' => !is_null($location),
            'gold'       => $character->gold,
            'character'  => $character,
        ]);
    }

    public function shopSellAll(Character $character) {

        $totalSoldFor = $this->shopService->sellAllItemsInInventory($character);

        $newGold = $character->gold + $totalSoldFor;

        if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
            $newGold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update([
            'gold' => $newGold,
        ]);

        if ($totalSoldFor === 0) {
            return redirect()->back()->with('error', 'You have nothing that you can sell.');
        }

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        return redirect()->back()->with('success', 'Sold all your unequipped items for a total of: ' . $totalSoldFor . ' gold.');
    }



    public function sell(Request $request, Character $character) {

        $inventorySlot = $character->inventory->slots->filter(function ($slot) use ($request) {
            return $slot->id === (int) $request->slot_id && !$slot->equipped;
        })->first();

        if (is_null($inventorySlot)) {
            return redirect()->back()->with('error', 'Item not found.');
        }

        $item         = $inventorySlot->item;
        $totalSoldFor = $this->shopService->sellItem($inventorySlot, $character);

        return redirect()->back()->with('success', 'Sold: ' . $item->affix_name . ' for: ' . $totalSoldFor . ' gold.');
    }

    public function shopCompare(Request $request, Character $character,
                                ComparisonService $comparisonService) {

        $viewData = $comparisonService->buildShopData($character, Item::where('name', $request->item_name)->first(), $request->item_type);

        Cache::put('shop-comparison-character-' . $character->id, $viewData, now()->addMinutes(10));

        return redirect()->to(route('game.shop.view.comparison', ['character' => $character]));
    }

    public function viewShopCompare(Character $character) {
        $cache = Cache::get('shop-comparison-character-' . $character->id);

        if (is_null($cache)) {
            return redirect()->to(route('game.shop.buy', ['character' => $character->id]))->with('error', 'Comparison cache has expired. Please click compare again. Cache expires after 10 minutes');
        }

        return view('game.core.comparison.comparison', [
            'itemToEquip' => $cache,
            'route' => route('game.shop.buy-and-replace', ['character' => $character->id])
        ]);
    }

    public function buyAndReplace(ShopReplaceItemValidation $request, Character $character) {

        $item = Item::find($request->item_id_to_buy);

        if ($item->craft_only) {
            return redirect()->back()->with('error', 'You are not capable of affording such luxury, child!');
        }

        $cost = $item->cost;

        if ($character->classType()->isMerchant()) {
            $cost = $cost - $cost * 0.25;
        }

        if ($cost > $character->gold) {
            return redirect()->back()->with('error', 'You do not have enough gold.');
        }

        if ($character->isInventoryFull()) {
            return redirect()->back()->with('error', 'Inventory is full. Please make room.');
        }

        $this->shopService->buyAndReplace($item, $character, $request->all());

        return redirect()->to(route('game.shop.buy', ['character' => $character]))->with('success', 'Purchased and equipped: ' . $item->affix_name . '.');
    }

    public function puracheMultiple(ShopBuyMultipleOfItem $request, Character $character) {
        $item = Item::where('name', $request->item_name)
            ->whereNotIn('type', ['alchemy', 'trinket', 'artifact', 'quest'])
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->first();

        if (is_null($item)) {
            return redirect()->back()->with('error', 'No matching item found ...');
        }

        return view('game.shop.multiple', [
            'gold'        => $character->gold,
            'cost'        => $item->cost,
            'itemId'      => $item->id,
            'itemName'    => $item->name,
            'characterId' => $character->id,
            'character'   => $character,
        ]);
    }

    public function buyMultiple(ShopPurchaseMultipleValidation $request, Character $character) {
        $item   = Item::find($request->item_id);
        $amount = $request->amount;

        if ($amount > $character->inventory_max || $character->isInventoryFull()) {
            return redirect()->back()->with('error', 'You cannot purchase more then you have inventory space.');
        }

        $cost = $amount * $item->cost;

        if ($character->classType()->isMerchant()) {
            $cost = $cost - $cost * 0.25;
        }

        if ($cost > $character->gold) {
            return redirect()->back()->with('error', 'You do not have enough gold.');
        }

        $this->shopService->buyMultipleItems($character, $item, $cost, $amount);

        return redirect()->to(route('game.shop.buy', ['character' => $character->id]))->with('success', 'You purchased: ' . $amount . ' of ' . $item->name);
    }
}
