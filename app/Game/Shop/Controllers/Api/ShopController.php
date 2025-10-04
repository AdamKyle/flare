<?php

namespace App\Game\Shop\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Pagination\Requests\PaginationRequest;
use App\Flare\Transformers\CharacterInventoryCountTransformer;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\ComparisonService;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Shop\Events\SellItemEvent;
use App\Game\Shop\Events\UpdateShopEvent;
use App\Game\Shop\Requests\ShopPurchaseMultipleValidation;
use App\Game\Shop\Requests\ShopReplaceItemValidation;
use App\Game\Shop\Requests\ShopSellValidation;
use App\Game\Shop\Services\ShopService;
use App\Http\Controllers\Controller;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;

class ShopController extends Controller
{

    public function __construct(private readonly CharacterInventoryService $characterInventoryService,
                                private readonly ShopService $shopService,
                                private readonly CharacterInventoryCountTransformer $characterInventoryCountTransformer,
                                private readonly Manager $manager,
    )
    {
    }

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
        ComparisonService $comparisonService)
    {

        $viewData = $comparisonService->buildShopData($character, Item::where('name', $request->item_name)->first(), $request->item_type);

        return response()->json(
            $viewData,
        );
    }

    public function buy(Request $request, Character $character)
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

    public function buyMultiple(ShopPurchaseMultipleValidation $request, Character $character)
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

    public function buyAndReplace(ShopReplaceItemValidation $request, Character $character)
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

        event(new UpdateShopEvent($character->user, $character->gold, $character->getInventoryCount()));

        return response()->json([
            'message' => 'Purchased and equipped: '.$item->affix_name.'.',
        ]);
    }

    public function sellItem(ShopSellValidation $request, Character $character)
    {

        $inventorySlot = $character->inventory->slots->filter(function ($slot) use ($request) {
            return $slot->id === (int) $request->slot_id && ! $slot->equipped;
        })->first();

        if (is_null($inventorySlot)) {
            return response()->json(['message' => 'Item not found.']);
        }

        $item = $inventorySlot->item;

        if ($item->type === 'trinket' || $item->type === 'artifact') {
            return response()->json(['message' => 'The shop keeper will not accept this item (Trinkets/Artifacts cannot be sold to the shop).']);
        }

        $totalSoldFor = SellItemCalculator::fetchSalePriceWithAffixes($item);

        $character = $character->refresh();

        event(new SellItemEvent($inventorySlot, $character));

        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json([
            'message' => 'Sold: '.$item->affix_name.' for: '.number_format($totalSoldFor).' gold.',
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory'),
            ],
        ]);
    }

    public function sellAll(Character $character)
    {

        $result = $this->shopService->sellAllItems($character);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
