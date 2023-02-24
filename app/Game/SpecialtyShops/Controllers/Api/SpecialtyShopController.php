<?php

namespace App\Game\SpecialtyShops\Controllers\Api;

use App\Flare\Events\ServerMessageEvent;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\SpecialtyShops\Requests\SpecialtyShopValidation;
use App\Game\SpecialtyShops\Requests\SpecialtyShopPurchaseValidation;
use App\Game\SpecialtyShops\Services\SpecialtyShop;
use Exception;
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
            foreach ($items as $item) {
                $goldCost       = $item->cost;
                $goldDustCost   = $item->gold_gold_dust_cost;
                $shardsCost     = $item->shards->shards_cost;
                $copperCoinCost = $item->copper_coin_cost;

                $goldCost       = $goldCost - $goldCost * 0.05;
                $goldDustCost   = $goldDustCost - $goldDustCost * 0.05;
                $shardsCost     = $shardsCost - $shardsCost * 0.05;
                $copperCoinCost = $copperCoinCost - $copperCoinCost * 0.05;

                $item->cost             = $goldCost;
                $item->gold_dust_cost   = $goldDustCost;
                $item->shards_cost      = $shardsCost;
                $item->copper_coin_cost = $copperCoinCost;
            }

            event(new ServerMessageEvent($character->user, 'As a merchant, you get 5% reduction on specialty shop costs. these have been applied to the list.'));
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
