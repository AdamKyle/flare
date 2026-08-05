<?php

namespace App\Game\Npcs\Actions\QueenOfHearts\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Pagination\Requests\PaginationRequest;
use App\Game\Npcs\Actions\QueenOfHearts\Requests\MoveRandomEnchantment;
use App\Game\Npcs\Actions\QueenOfHearts\Requests\QueenDestinationItemsRequest;
use App\Game\Npcs\Actions\QueenOfHearts\Requests\ReRollRandomEnchantment;
use App\Game\Npcs\Actions\QueenOfHearts\Services\QueenOfHeartsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class QueenOfHeartsController extends Controller
{
    private QueenOfHeartsService $queenOfHeartsService;

    public function __construct(QueenOfHeartsService $queenOfHeartsService)
    {
        $this->queenOfHeartsService = $queenOfHeartsService;
    }

    public function uniquesOnly(Character $character): JsonResponse
    {
        return response()->json($this->queenOfHeartsService->buildQueenResponse($character));
    }

    public function uniqueItems(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->queenOfHeartsService->fetchPaginatedUniqueItems($character, $request->per_page, $request->page, $request->search_text)
        );
    }

    public function destinationItems(QueenDestinationItemsRequest $request, Character $character): JsonResponse
    {
        $result = $this->queenOfHeartsService->fetchPaginatedDestinationItems(
            $character,
            $request->source_slot_id,
            $request->per_page,
            $request->page,
            $request->search_text,
        );

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function reRoll(ReRollRandomEnchantment $request, Character $character): JsonResponse
    {
        $result = $this->queenOfHeartsService->reRollUnique($character, $request->selected_slot_id, $request->selected_reroll_type, $request->selected_affix);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function moveAffixes(MoveRandomEnchantment $request, Character $character): JsonResponse
    {
        $result = $this->queenOfHeartsService->moveAffixes(
            $character,
            $request->selected_slot_id,
            $request->selected_secondary_slot_id,
            $request->selected_affix,
        );

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
