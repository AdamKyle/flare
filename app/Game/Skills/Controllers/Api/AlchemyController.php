<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Pagination\Requests\PaginationRequest;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Skills\Requests\AlchemyValidation;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Transformers\AlchemyItemTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class AlchemyController extends Controller
{
    use ChecksAutomationRestrictions;

    public function __construct(
        private AlchemyService $alchemyService,
        private CraftingService $craftingService,
        private readonly AlchemyItemTransformer $alchemyItemTransformer,
    ) {}

    public function alchemyItems(Character $character): JsonResponse
    {
        return response()->json([
            'items' => $this->transformItems($this->alchemyService->fetchAlchemistItems($character)),
            'skill_xp' => $this->alchemyService->fetchSkillXP($character),
            'inventory_count' => $this->craftingService->getAlchemyBagCount($character),
        ]);
    }

    public function items(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->alchemyService->fetchPaginatedAlchemistItems($character, $request->per_page, $request->page, $request->search_text)
        );
    }

    public function transmute(AlchemyValidation $request, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::START_CRAFTING);

        if (! is_null($restriction)) {
            return $restriction;
        }

        if (! $character->can_craft) {
            return response()->json(['message' => 'You must wait to craft again.'], 422);
        }

        $alchemyResult = $this->alchemyService->transmute($character, $request->item_to_craft);

        return response()->json([
            'items' => $this->transformItems($this->alchemyService->fetchAlchemistItems($character, false)),
            'skill_xp' => $this->alchemyService->fetchSkillXP($character),
            'inventory_count' => $this->craftingService->getAlchemyBagCount($character),
            'alchemy_result' => $alchemyResult,
        ]);
    }

    private function transformItems(iterable $items): array
    {
        return (new Collection($items))
            ->map(fn ($item) => $this->alchemyItemTransformer->transform($item))
            ->values()
            ->all();
    }
}
