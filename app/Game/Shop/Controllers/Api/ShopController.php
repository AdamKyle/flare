<?php

namespace App\Game\Shop\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Pagination\Requests\PaginationRequest;
use App\Flare\Transformers\CharacterInventoryCountTransformer;
use App\Game\Character\CharacterInventory\Services\ComparisonService;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Shop\Requests\ShopPurchaseMultipleValidation;
use App\Game\Shop\Requests\ShopReplaceItemValidation;
use App\Game\Shop\Services\ShopService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;

class ShopController extends Controller
{
    public function __construct(
        private readonly ShopService $shopService,
        private readonly CharacterInventoryCountTransformer $characterInventoryCountTransformer,
        private readonly Manager $manager,
    ) {}

    public function fetchItemsForShop(PaginationRequest $paginationRequest, Character $character): JsonResponse
    {

        return response()->json(
            $this->shopService->getItemsForShop(
                $character,
                $paginationRequest->per_page,
                $paginationRequest->page,
                $paginationRequest->search_text,
                $paginationRequest->filters
            ),
        );
    }

    public function shopCompare(Request $request, Character $character,
        ComparisonService $comparisonService): JsonResponse
    {

        $viewData = $comparisonService->buildShopData($character, Item::where('name', $request->item_name)->first(), $request->item_type);

        return response()->json(
            $viewData,
        );
    }

    public function buy(Request $request, Character $character): JsonResponse|\Illuminate\Http\RedirectResponse
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

        $data = new FractalItem($character, $this->characterInventoryCountTransformer);
        $data = $this->manager->createData($data)->toArray();

        return response()->json([
            'message' => 'Purchased: '.$item->affix_name.'.',
            'inventory_count' => $data,
            'gold' => $character->gold,
        ]);
    }

    public function buyMultiple(ShopPurchaseMultipleValidation $request, Character $character): JsonResponse|\Illuminate\Http\RedirectResponse
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

        $data = new FractalItem($character, $this->characterInventoryCountTransformer);
        $data = $this->manager->createData($data)->toArray();

        return response()->json([
            'message' => 'You purchased: '.$amount.' of '.$item->name,
            'inventory_count' => $data,
            'gold' => $character->gold,
        ]);
    }

    public function buyAndReplace(ShopReplaceItemValidation $request, Character $character): JsonResponse|\Illuminate\Http\RedirectResponse
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

        $this->shopService->buyAndReplace($item, $character, $request->all());

        $character = $character->refresh();

        $data = new FractalItem($character, $this->characterInventoryCountTransformer);
        $data = $this->manager->createData($data)->toArray();

        return response()->json([
            'message' => 'Purchased and equipped: '.$item->affix_name.'.',
            'inventory_count' => $data,
            'gold' => $character->gold,
        ]);
    }
}
