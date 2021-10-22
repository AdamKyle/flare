<?php

namespace App\Game\Core\Controllers;

use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Game\Core\Events\UpdateAttackStats;
use App\Game\Core\Exceptions\EquipItemException;
use App\Game\Core\Services\EquipItemService;
use Cache;
use App\Game\Core\Jobs\PurchaseItemsJob;
use App\Game\Core\Requests\EquipItemValidation;
use App\Game\Core\Services\ComparisonService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Game\Core\Events\BuyItemEvent;
use App\Game\Core\Events\SellItemEvent;
use App\Game\Core\Services\ShopService;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;

class ShopController extends Controller {

    private $equipItemService;

    private $buildCharacterAttackTypes;

    private $characterTransformer;

    private $manager;

    public function __construct(
        EquipItemService $equipItemService,
        BuildCharacterAttackTypes $buildCharacterAttackTypes,
        CharacterAttackTransformer $characterTransformer,
        Manager $manager
    ) {
        $this->middleware('auth');
        $this->middleware('is.character.dead');
        $this->middleware('is.character.adventuring');

        $this->equipItemService          = $equipItemService;
        $this->buildCharacterAttackTypes = $buildCharacterAttackTypes;
        $this->characterTransformer      = $characterTransformer;
        $this->manager                   = $manager;
    }

    public function shopBuy(Character $character) {

        $location = Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->first();

        return view('game.core.shop.buy', [
            'isLocation' => !is_null($location),
            'gold'       => $character->gold,
            'character'  => $character,
        ]);
    }

    public function shopSell(Character $character) {

        $location = Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->first();

        return view('game.core.shop.sell', [
            'isLocation' => !is_null($location),
            'gold'       => $character->gold,
            'character'  => $character,
        ]);
    }

    public function shopSellAll(Character $character, ShopService $service) {

        $totalSoldFor = $service->sellAllItemsInInventory($character);

        $character->update([
            'gold' => $character->gold + $totalSoldFor,
        ]);

        if ($totalSoldFor === 0) {
            return redirect()->back()->with('error', 'You have nothing that you can sell.');
        }

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

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

        $item = $inventorySlot->item;

        event(new SellItemEvent($inventorySlot, $character));

        $totalSoldFor = SellItemCalculator::fetchTotalSalePrice($item);

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        return redirect()->back()->with('success', 'Sold: ' . $item->affix_name . ' for: ' . $totalSoldFor . ' gold.');
    }

    public function shopSellBulk(Request $request, Character $character, ShopService $service) {
        $inventorySlots = $character->inventory->slots()->findMany($request->slots);

        if ($inventorySlots->isEmpty()) {
            return redirect()->back()->with('error', 'No items could be found. Did you select any?');
        }

        $totalSoldFor = $service->fetchTotalSoldFor($inventorySlots, $character);

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

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

        return view('game.core.shop.comparison', $cache);
    }

    public function buyAndReplace(EquipItemValidation $request, Character $character) {
        if (!$request->has('item_id_to_buy')) {
            return redirect()->to(route('game.shop.buy', ['character' => $character->id]))->with('error', 'Missing item to buy. Invalid Input.');
        }

        $item = Item::find($request->item_id_to_buy);

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

        $character = $character->refresh();

        $slot = $character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item->id === $item->id && !$slot->equipped;
        })->first();

        $request->merge([
            'slot_id' => $slot->id,
        ]);

        try {
            $this->equipItemService->setRequest($request)
                ->setCharacter($character)
                ->replaceItem();

            $this->updateCharacterAttakDataCache($character);

            event(new CharacterInventoryUpdateBroadCastEvent($character->user));

            return redirect()->to(route('game.character.sheet'))->with('success', 'Purchased and equipped: ' . $item->affix_name . '.');

        } catch(EquipItemException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    protected function updateCharacterAttakDataCache(Character $character) {
        $this->buildCharacterAttackTypes->buildCache($character);

        $characterData = new ResourceItem($character->refresh(), $this->characterTransformer);

        $characterData = $this->manager->createData($characterData)->toArray();

        event(new UpdateAttackStats($characterData, $character->user));
    }
}
