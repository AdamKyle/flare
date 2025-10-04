<?php

namespace App\Game\NpcActions\QueenOfHeartsActions\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\NpcActions\QueenOfHeartsActions\Requests\MoveRandomEnchantment;
use App\Game\NpcActions\QueenOfHeartsActions\Requests\ReRollRandomEnchantment;
use App\Game\NpcActions\QueenOfHeartsActions\Services\QueenOfHeartsService;
use App\Game\NpcActions\QueenOfHeartsActions\Services\RandomEnchantmentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class QueenOfHeartsController extends Controller
{
    private QueenOfHeartsService $queenOfHeartsService;

    public function __construct(QueenOfHeartsService $queenOfHeartsService)
    {
        $this->queenOfHeartsService = $queenOfHeartsService;
    }

    public function uniquesOnly(Character $character, RandomEnchantmentService $randomEnchantmentService): JsonResponse
    {
        return response()->json($randomEnchantmentService->fetchDataForApi($character));
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
