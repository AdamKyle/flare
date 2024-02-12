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
            'inventory' => array_values($character->refresh()->inventory->slots->filter(function($slot) {
                return $slot->item->type !== 'artifact' && $slot->item->type !== 'trinket' && $slot->item->type !== 'quest' && $slot->item->type !== 'alchemy';
            })->map(function($slot) {
                return [
                    'affix_name' => $slot->item->affix_name,
                    'id' => $slot->item_id,
                ];
            })->toArray()),
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
            $request->item_id_from,
            $request->item_id_to
        );

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

}
