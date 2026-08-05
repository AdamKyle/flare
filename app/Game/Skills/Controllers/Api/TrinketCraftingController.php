<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Pagination\Requests\PaginationRequest;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Skills\Requests\TrinketCraftingValidation;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TrinketCraftingController extends Controller
{
    use ChecksAutomationRestrictions;

    public function __construct(private TrinketCraftingService $trinketCraftingService, private CraftingService $craftingService) {}

    public function fetchItemsToCraft(Character $character): JsonResponse
    {

        return response()->json([
            'items' => $this->trinketCraftingService->fetchItemsToCraft($character),
            'skill_xp' => $this->trinketCraftingService->fetchSkillXP($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
        ]);
    }

    public function items(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->trinketCraftingService->fetchPaginatedItemsToCraft($character, $request->per_page, $request->page, $request->search_text)
        );
    }

    public function craftTrinket(TrinketCraftingValidation $request, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::START_CRAFTING);

        if (! is_null($restriction)) {
            return $restriction;
        }

        event(new CraftedItemTimeOutEvent($character));

        $item = Item::find($request->item_to_craft);

        $result = $this->trinketCraftingService->craft($character, $item);

        return response()->json([
            'items' => $result['items'],
            'result_preview' => $result['result_preview'],
            'skill_xp' => $this->trinketCraftingService->fetchSkillXP($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
        ]);
    }
}
