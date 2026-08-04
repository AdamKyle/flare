<?php

namespace App\Game\Npcs\Actions\LabyrinthOracle\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Npcs\Actions\LabyrinthOracle\Requests\ItemTransferRequest;
use App\Game\Npcs\Actions\LabyrinthOracle\Services\ItemTransferService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LabyrinthOracleController extends Controller
{
    private ItemTransferService $itemTransferService;

    public function __construct(ItemTransferService $itemTransferService)
    {
        $this->itemTransferService = $itemTransferService;
    }

    public function inventoryItems(Character $character): JsonResponse
    {

        return response()->json([
            'inventory' => $this->itemTransferService->fetchInventoryItems($character),
            'costs' => $this->itemTransferService->getCosts(),
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function transferItem(ItemTransferRequest $request, Character $character)
    {
        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $request->item_id_from,
            $request->item_id_to
        );

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
