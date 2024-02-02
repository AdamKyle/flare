<?php

namespace App\Game\NpcActions\LabyrinthOracle\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\NpcActions\LabyrinthOracle\Services\ItemTransferService;
use App\Game\NpcActions\LabyrinthOracle\Requests\ItemTransferRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LabyrinthOracleController extends Controller {

    /**
     * @var ItemTransferService $itemTransferService
     */
    private ItemTransferService $itemTransferService;

    /**
     * @param ItemTransferService $itemTransferService
     */
    public function __construct(ItemTransferService $itemTransferService) {
        $this->itemTransferService = $itemTransferService;
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function inventoryItems(Character $character): JsonResponse {

        return response()->json([
            'inventory' => $character->inventory->slots->pluck('item.name', 'item.id')->toArray(),
        ]);
    }

    /**
     * @param ItemTransferRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function transferItem(ItemTransferRequest $request, Character $character) {
        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $request->currency_costs,
            $request->item_id_from,
            $request->item_id_to
        );

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

}
