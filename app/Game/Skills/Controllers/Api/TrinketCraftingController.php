<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Skills\Requests\TrinketCraftingValidation;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TrinketCraftingController extends Controller
{

    public function __construct(private TrinketCraftingService $trinketCraftingService) {}

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function fetchItemsToCraft(Character $character): JsonResponse
    {

        return response()->json([
            'items' => $this->trinketCraftingService->fetchItemsToCraft($character),
            'skill_xp' => $this->trinketCraftingService->fetchSkillXP($character),
        ]);
    }

    /**
     * @param TrinketCraftingValidation $request
     * @param Character $character
     * @return JsonResponse
     */
    public function craftTrinket(TrinketCraftingValidation $request, Character $character): JsonResponse
    {
        event(new CraftedItemTimeOutEvent($character));

        $item = Item::find($request->item_to_craft);

        return response()->json([
            'items' => $this->trinketCraftingService->craft($character, $item),
            'skill_xp' => $this->trinketCraftingService->fetchSkillXP($character),
        ]);
    }
}
