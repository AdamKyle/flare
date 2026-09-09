<?php

namespace App\Game\Shop\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Pagination\Requests\PaginationRequest;
use App\Game\Character\CharacterInventory\Exceptions\EquipItemException;
use App\Game\Character\CharacterInventory\Services\ComparisonService;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Shop\Events\UpdateShopEvent;
use App\Game\Shop\Requests\ShopPurchaseMultipleValidation;
use App\Game\Shop\Requests\ShopReplaceItemValidation;
use App\Game\Shop\Services\ShopService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(
        private readonly ShopService $shopService,
        private readonly ComparisonService $comparisonService,
        private readonly CharacterInventoryCountTransformer $characterInventoryCountTransformer,
    ) {}

    /**
     * Paginate the Shop's purchasable Items for the character.
     */
    public function fetchItemsForShop(PaginationRequest $request, Character $character): JsonResponse
    {
        $filters = $request->filters;

        $type = $filters['type'] ?? null;
        $sortCost = $filters['sort_cost'] ?? null;

        return response()->json(
            $this->shopService->getItemsForShop($character, $type, $request->search_text, $sortCost, $request->per_page, $request->page)
        );
    }

    /**
     * Build the shop item-comparison data for the character.
     */
    public function shopCompare(Request $request, Character $character): JsonResponse
    {

        $viewData = $this->comparisonService->buildShopData($character, Item::where('name', $request->item_name)->first(), $request->item_type);

        return response()->json([
            'comparison_data' => $viewData,
        ]);
    }

    /**
     * Purchase a single Shop Item for the character.
     */
    public function buy(Request $request, Character $character): JsonResponse
    {

        if ($character->gold === 0) {
            return response()->json([
                'message' => 'You do not have enough gold.',
            ], 422);
        }

        $item = Item::find($request->item_id);

        if (is_null($item)) {
            return response()->json([
                'message' => 'Item not found.',
            ], 422);
        }

        $cost = $item->cost;

        if ($character->classType()->isMerchant()) {
            $cost = floor($cost - $cost * 0.25);
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

        $character = $character->refresh();

        event(new BuyItemEvent($item, $character));

        event(new UpdateShopEvent($character->user, $character->gold, $character->getInventoryCount()));

        return response()->json([
            'message' => 'Purchased: '.$item->affix_name.'.',
            'gold' => $character->gold,
            'inventory_count' => $this->characterInventoryCountTransformer->transform($character),
        ]);
    }

    /**
     * Purchase multiple stacked units of a Shop Item for the character.
     */
    public function buyMultiple(ShopPurchaseMultipleValidation $request, Character $character): JsonResponse
    {
        $item = Item::find($request->item_id);
        $amount = $request->amount;

        if ($amount > $character->inventory_max || $character->isInventoryFull()) {
            return response()->json([
                'message' => 'You cannot purchase more then you have inventory space.',
            ], 422);
        }

        $cost = $amount * $item->cost;

        if ($character->classType()->isMerchant()) {
            $cost = $cost - $cost * 0.25;
        }

        if ($cost > $character->gold) {
            return response()->json([
                'message' => 'You do not have enough gold.',
            ], 422);
        }

        $this->shopService->buyMultipleItems($character, $item, $cost, $amount);

        $character = $character->refresh();

        event(new UpdateShopEvent($character->user, $character->gold, $character->getInventoryCount()));

        return response()->json([
            'message' => 'You purchased: '.$amount.' of '.$item->name,
            'gold' => $character->gold,
            'inventory_count' => $this->characterInventoryCountTransformer->transform($character),
        ]);
    }

    /**
     * Purchase a Shop Item and equip it in place of a currently equipped item for the character.
     */
    public function buyAndReplace(ShopReplaceItemValidation $request, Character $character): JsonResponse
    {

        $item = Item::find($request->item_id_to_buy);

        if ($item->craft_only) {
            return response()->json([
                'message' => 'You are not capable of affording such luxury, child!',
            ], 422);
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
            'gold' => $character->gold,
            'inventory_count' => $this->characterInventoryCountTransformer->transform($character),
        ]);
    }
}
