<?php

namespace App\Game\Shop\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Pagination\Requests\PaginationRequest;
use App\Game\Character\CharacterInventory\Exceptions\EquipItemException;
use App\Game\Character\CharacterInventory\Services\ComparisonService;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Shop\Events\UpdateShopEvent;
use App\Game\Shop\Requests\ShopPurchaseMultipleValidation;
use App\Game\Shop\Requests\ShopReplaceItemValidation;
use App\Game\Shop\Services\ShopService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(
        private readonly ShopService $shopService,
        private readonly ComparisonService $comparisonService
    ) {
    }

    public function fetchItemsForShop(PaginationRequest $request, Character $character): JsonResponse
    {
        $filters = $request->filters;

        $type = $filters['type'] ?? null;
        $sortCost = $filters['sort_cost'] ?? null;

        return response()->json(
            $this->shopService->getItemsForShop($character, $type, $request->search_text, $sortCost, $request->per_page, $request->page)
        );
    }

    public function shopCompare(Request $request, Character $character): JsonResponse
    {

        $viewData = $this->comparisonService->buildShopData($character, Item::where('name', $request->item_name)->first(), $request->item_type);

        return response()->json([
            'comparison_data' => $viewData,
        ]);
    }

    public function buy(Request $request, Character $character): JsonResponse|RedirectResponse
    {

        if ($character->gold === 0) {
            return redirect()->back()->with('error', 'You do not have enough gold.');
        }

        $item = Item::find($request->item_id);

        if (is_null($item)) {
            return redirect()->back()->with('error', 'Item not found.');
        }

        $cost = $item->cost;

        if ($character->classType()->isMerchant()) {
            $cost = floor($cost - $cost * 0.25);
        }

        if ($cost > $character->gold) {
            return redirect()->back()->with('error', 'You do not have enough gold.');
        }

        if ($character->isInventoryFull()) {
            return redirect()->back()->with('error', 'Inventory is full. Please make room.');
        }

        $character = $character->refresh();

        event(new BuyItemEvent($item, $character));

        event(new UpdateShopEvent($character->user, $character->gold, $character->getInventoryCount()));

        return response()->json([
            'message' => 'Purchased: '.$item->affix_name.'.',
        ]);
    }

    public function buyMultiple(ShopPurchaseMultipleValidation $request, Character $character): JsonResponse|RedirectResponse
    {
        $item = Item::find($request->item_id);
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

        $character = $character->refresh();

        event(new UpdateShopEvent($character->user, $character->gold, $character->getInventoryCount()));

        return response()->json([
            'message' => 'You purchased: '.$amount.' of '.$item->name,
        ]);
    }

    public function buyAndReplace(ShopReplaceItemValidation $request, Character $character): JsonResponse|RedirectResponse
    {

        $item = Item::find($request->item_id_to_buy);

        if ($item->craft_only) {
            return redirect()->back()->with('error', 'You are not capable of affording such luxury, child!');
        }

        $cost = $item->cost;

        if ($character->classType()->isMerchant()) {
            $cost = $cost - $cost * 0.25;
        }

        if ($cost > $character->gold) {
            return response()->json([
                'message' => 'You do not have enough gold.',
            ], 422);
        }

        if ($character->isInventoryFull()) {
            return response()->json([
                'message' => 'Inventory is full. Please make room.',
            ], 422);
        }

        try {
            $this->shopService->buyAndReplace($item, $character, $request->all());
        } catch (EquipItemException $e) {
            return response()->json([
                'message' => 'Could not complete purchase.',
            ], 422);
        }

        $character = $character->refresh();

        event(new UpdateShopEvent($character->user, $character->gold, $character->getInventoryCount()));

        return response()->json([
            'message' => 'Purchased and equipped: '.$item->affix_name.'.',
        ]);
    }

}
