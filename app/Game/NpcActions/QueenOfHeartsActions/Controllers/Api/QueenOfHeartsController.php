<?php

namespace App\Game\NpcActions\QueenOfHeartsActions\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\NpcActions\QueenOfHeartsActions\Requests\MoveRandomEnchantment;
use App\Game\NpcActions\QueenOfHeartsActions\Requests\PurchaseRandomEnchantment;
use App\Game\NpcActions\QueenOfHeartsActions\Requests\ReRollRandomEnchantment;
use App\Game\NpcActions\QueenOfHeartsActions\Services\QueenOfHeartsService;
use App\Game\NpcActions\QueenOfHeartsActions\Services\RandomEnchantmentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class QueenOfHeartsController extends Controller {

    /**
     * @var QueenOfHeartsService $queenOfHeartsService
     */
    private QueenOfHeartsService $queenOfHeartsService;

    /**
     * @param QueenOfHeartsService $queenOfHeartsService
     */
    public function __construct(QueenOfHeartsService $queenOfHeartsService) {
        $this->queenOfHeartsService = $queenOfHeartsService;
    }

    /**
     * @param Character $character
     * @param RandomEnchantmentService $randomEnchantmentService
     * @return JsonResponse
     */
    public function uniquesOnly(Character $character, RandomEnchantmentService $randomEnchantmentService): JsonResponse {
        return response()->json($randomEnchantmentService->fetchDataForApi($character));
    }

    /**
     * @param PurchaseRandomEnchantment $request
     * @param Character $character
     * @return JsonResponse
     */
    public function purchase(PurchaseRandomEnchantment $request, Character $character): JsonResponse {

        $result = $this->queenOfHeartsService->purchaseUnique($character, $request->type);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param ReRollRandomEnchantment $request
     * @param Character $character
     * @return JsonResponse
     */
    public function reRoll(ReRollRandomEnchantment $request, Character $character): JsonResponse {
        $result = $this->queenOfHeartsService->reRollUnique($character, $request->selected_slot_id, $request->selected_reroll_type, $request->selected_affix);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param MoveRandomEnchantment $request
     * @param Character $character
     * @return JsonResponse
     */
    public function moveAffixes(MoveRandomEnchantment $request, Character $character): JsonResponse {
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
