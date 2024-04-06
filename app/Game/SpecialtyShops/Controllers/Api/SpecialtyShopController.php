<?php

namespace App\Game\SpecialtyShops\Controllers\Api;

use App\Flare\Values\MaxCurrenciesValue;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\SpecialtyShops\Requests\SpecialtyShopValidation;
use App\Game\SpecialtyShops\Requests\SpecialtyShopPurchaseValidation;
use App\Game\SpecialtyShops\Services\SpecialtyShop;
use App\Game\Messages\Events\ServerMessageEvent;

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
     * @throws Exception
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

        if ($character->classType()->isMerchant()) {

            $items = $items->transform(function($item) {
                $goldCost       = $item->cost;
                $goldDustCost   = $item->gold_gold_dust_cost;
                $shardsCost     = $item->shards_cost;
                $copperCoinCost = $item->copper_coin_cost;

                $goldCost       = $goldCost - $goldCost * 0.05;
                $goldDustCost   = $goldDustCost - $goldDustCost * 0.05;
                $shardsCost     = $shardsCost - $shardsCost * 0.05;
                $copperCoinCost = $copperCoinCost - $copperCoinCost * 0.05;

                $goldCost = min($goldCost, MaxCurrenciesValue::MAX_GOLD);
                $goldDustCost = min($goldDustCost, MaxCurrenciesValue::MAX_GOLD_DUST);
                $shardsCost = min($shardsCost, MaxCurrenciesValue::MAX_SHARDS);
                $copperCoinCost = min($copperCoinCost, MaxCurrenciesValue::MAX_COPPER);

                $item->cost             = $goldCost;
                $item->gold_dust_cost   = $goldDustCost;
                $item->shards_cost      = $shardsCost;
                $item->copper_coin_cost = $copperCoinCost;

                return $item;
            });

            event(new ServerMessageEvent($character->user, 'As a Merchant, you get 5% reduction on specialty shop costs. This has been applied to the cost calculation when you select an item.'));
        }

        return response()->json([
            'items' => $items,
        ]);
    }

    /**
     * @param SpecialtyShopPurchaseValidation $request
     * @param Character $character
     * @return JsonResponse
     * @throws Exception
     */
    public function purchaseItem(SpecialtyShopPurchaseValidation $request, Character $character): JsonResponse {
        $result = $this->specialtyShop->purchaseItem($character, $request->item_id, $request->type);

        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}
