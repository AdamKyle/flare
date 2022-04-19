<?php

namespace App\Game\Shop\Controllers;

use Cache;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;
use App\Http\Controllers\Controller;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Events\UpdateTopBarEvent;
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


class ShopController extends Controller {

    private $equipItemService;

    private $buildCharacterAttackTypes;

    private $characterSheetBaseInfoTransformer;

    private $manager;

    public function __construct(
        EquipItemService $equipItemService,
        BuildCharacterAttackTypes $buildCharacterAttackTypes,
        CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer,
        Manager $manager
    ) {
        $this->middleware('auth');
        $this->middleware('is.character.dead');
        $this->middleware('is.character.adventuring');

        $this->equipItemService                       = $equipItemService;
        $this->buildCharacterAttackTypes              = $buildCharacterAttackTypes;
        $this->characterSheetBaseInfoTransformer      = $characterSheetBaseInfoTransformer;
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

    public function shopSellAll(Character $character, ShopService $service) {

        $totalSoldFor = $service->sellAllItemsInInventory($character);

        $maxCurrencies = new MaxCurrenciesValue($character->gold + $totalSoldFor, MaxCurrenciesValue::GOLD);

        if ($maxCurrencies->canNotGiveCurrency()) {
            $character->update([
                'gold' => MaxCurrenciesValue::MAX_GOLD,
            ]);
        } else {
            $character->update([
                'gold' => $character->gold + $totalSoldFor,
            ]);
        }

        if ($totalSoldFor === 0) {
            return redirect()->back()->with('error', 'You have nothing that you can sell.');
        }

        $character = $character->refresh();

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        event(new UpdateTopBarEvent($character));

        return redirect()->back()->with('success', 'Sold all your unequipped items for a total of: ' . $totalSoldFor . ' gold.');
    }

    public function shopBuyBulk(Request $request, Character $character) {

        if ($character->gold === 0) {
            return redirect()->back()->with('error', 'You do not have enough gold.');
        }

        $items = Item::findMany($request->items);

        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'No items could be found. Did you select any?');
        }

        PurchaseItemsJob::dispatch($character, $items)->onConnection('shop_buying');

        return redirect()->back()->with('success', 'Your items are being purchased.
        You can check your character sheet to see them come in. If you cannot afford the items, the game chat section will update.
        Once all items are purchased, the chat section will update to inform you.');

    }

    public function buy(Request $request, Character $character) {

        if ($character->gold === 0) {
            return redirect()->back()->with('error', 'You do not have enough gold.');
        }

        $item = Item::find($request->item_id);

        if (is_null($item)) {
            return redirect()->back()->with('error', 'Item not found.');
        }

        if ($item->cost > $character->gold) {
            return redirect()->back()->with('error', 'You do not have enough gold.');
        }

        if ($character->isInventoryFull()) {
            return redirect()->back()->with('error', 'Inventory is full. Please make room.');
        }

        event(new BuyItemEvent($item, $character));

        return redirect()->back()->with('success', 'Purchased: ' . $item->affix_name . '.');
    }

    public function sell(Request $request, Character $character) {

        $inventorySlot = $character->inventory->slots->filter(function($slot) use($request) {
            return $slot->id === (int) $request->slot_id && !$slot->equipped;
        })->first();

        if (is_null($inventorySlot)) {
            return redirect()->back()->with('error', 'Item not found.');
        }

        $item         = $inventorySlot->item;
        $totalSoldFor = SellItemCalculator::fetchTotalSalePrice($item);

        event(new SellItemEvent($inventorySlot, $character));

        return redirect()->back()->with('success', 'Sold: ' . $item->affix_name . ' for: ' . $totalSoldFor . ' gold.');
    }

    public function shopSellBulk(Request $request, Character $character, ShopService $service) {
        if (empty($request->slots)) {
            return redirect()->back()->with('error', 'No items could be found. Did you select any?');
        }

        $inventory = Inventory::where('character_id', $character->id)->first();
        $slots     = InventorySlot::whereIn('id', $request->slots)->where('inventory_id', $inventory->id)->get();

        $totalSoldFor = $service->fetchTotalSoldFor($slots, $character);

        $newGold      = $character->gold + $totalSoldFor;

        $maxCurrencies = new MaxCurrenciesValue($newGold, MaxCurrenciesValue::GOLD);

        if ($maxCurrencies->canNotGiveCurrency()) {
            $newGold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update([
            'gold' => $newGold
        ]);

        InventorySlot::whereIn('id', $request->slots)->where('inventory_id', $inventory->id)->delete();

        $character = $character->refresh();

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        event(new UpdateTopBarEvent($character));

        return redirect()->back()->with('success', 'Sold selected items for: ' . $totalSoldFor . ' gold.');
    }

    public function shopCompare(Request $request, Character $character, ComparisonService $comparisonService) {

        $viewData = $comparisonService->buildShopData($character, Item::find($request->item_id), $request->item_type);

        Cache::put('shop-comparison-character-' . $character->id, $viewData, now()->addMinutes(10));

        return redirect()->to(route('game.shop.view.comparison', ['character' => $character]));
    }

    public function viewShopCompare(Character $character) {
        $cache = Cache::get('shop-comparison-character-' . $character->id);

        if (is_null($cache)) {
            return redirect()->to(route('game.shop.buy', ['character' => $character->id]))->with('error', 'Comparison cache has expired. Please click compare again. Cache expires after 10 minutes');
        }

        return view('game.shop.comparison', $cache);
    }

    public function buyAndReplace(ShopReplaceItemValidation $request, Character $character) {

        $item = Item::find($request->item_id_to_buy);

        if ($item->craft_only) {
            return redirect()->back()->with('error', 'You are not capable of affording such luxury, child!');
        }

        if ($item->cost > $character->gold) {
            return redirect()->back()->with('error', 'You do not have enough gold.');
        }

        if ($character->isInventoryFull()) {
            return redirect()->back()->with('error', 'Inventory is full. Please make room.');
        }

        event(new BuyItemEvent($item, $character));

        $character = $character->refresh();

        $inventory = Inventory::where('character_id', $character->id)->first();

        $slot      = InventorySlot::where('equipped', false)->where('item_id', $item->id)->first();

        $request->merge([
            'slot_id' => $slot->id,
        ]);

        $this->equipItemService->setRequest($request)
            ->setCharacter($character)
            ->replaceItem();

        $this->updateCharacterAttackDataCache($character);

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'equipped'));
        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'sets'));

        event(new CharacterInventoryDetailsUpdate($character->user));

        return redirect()->to(route('game.shop.buy', ['character' => $character]))->with('success', 'Purchased and equipped: ' . $item->affix_name . '.');
    }

    protected function updateCharacterAttackDataCache(Character $character) {
        $this->buildCharacterAttackTypes->buildCache($character);

        $characterData = new ResourceItem($character->refresh(), $this->characterSheetBaseInfoTransformer);

        $characterData = $this->manager->createData($characterData)->toArray();

        event(new UpdateBaseCharacterInformation($character->user, $characterData));
    }
}
