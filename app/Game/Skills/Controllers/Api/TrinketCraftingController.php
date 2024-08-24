<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Skills\Requests\TrinketCraftingValidation;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Http\Controllers\Controller;

class TrinketCraftingController extends Controller
{
    private $trinketCraftingService;

    public function __construct(TrinketCraftingService $trinketCraftingService)
    {
        $this->trinketCraftingService = $trinketCraftingService;
    }

    public function fetchItemsToCraft(Character $character)
    {

        return response()->json([
            'items' => $this->trinketCraftingService->fetchItemsToCraft($character),
            'skill_xp' => $this->trinketCraftingService->fetchSkillXP($character),
        ]);
    }

    public function craftTrinket(TrinketCraftingValidation $request, Character $character)
    {
        event(new CraftedItemTimeOutEvent($character));

        $item = Item::find($request->item_to_craft);

        return response()->json([
            'items' => $this->trinketCraftingService->craft($character, $item),
            'skill_xp' => $this->trinketCraftingService->fetchSkillXP($character),
        ]);
    }
}
