<?php

namespace App\Game\SpecialtyShops\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\SpecialtyShops\Requests\SpecialtyShopValidation;
use App\Game\SpecialtyShops\Requests\SpecialtyShopPurchaseValidation;
use App\Game\SpecialtyShops\Services\SpecialtyShop;
use Illuminate\Http\JsonResponse;

class SpecialtyShopController extends Controller {

    /**
     * @var SpecialtyShop $specialtyShop
     */
    private SpecialtyShop $specialtyShop;

    /**
     * @param SpecialtyShop $specialtyShop
     */
    public function __construct(SpecialtyShop $specialtyShop) {
        $this->specialtyShop = $specialtyShop;
    }

    /**
     * @param SpecialtyShopValidation $request
     * @param Character $character
     * @return JsonResponse
     */
    public function fetchItems(SpecialtyShopValidation $request, Character $character): JsonResponse {
        $items = Item::where('specialty_type', $request->type)
                     ->whereNull('item_suffix_id')
                     ->whereNull('item_prefix_id')
                     ->doesntHave('appliedHolyStacks')
                     ->select('id', 'name', 'cost', 'shards_cost', 'copper_coin_cost', 'gold_dust_cost', 'type')->get();

        if ($items->isEmpty()) {
            return response()->json(['message' => 'no items found for this shop.'], 422);
        }

        return response()->json([
            'items' => $items,
        ]);
    }

    /**
     * @param SpecialtyShopPurchaseValidation $request
     * @param Character $character
     * @return JsonResponse
     * @throws \Exception
     */
    public function purchaseItem(SpecialtyShopPurchaseValidation $request, Character $character): JsonResponse {
        $result = $this->specialtyShop->purchaseItem($character, $request->item_id, $request->type);

        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}
