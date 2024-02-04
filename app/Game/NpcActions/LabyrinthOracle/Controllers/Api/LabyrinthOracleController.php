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
            'inventory' => $character->inventory->slots->filter(function($slot) {
                $itemIsValid = $slot->item->type !== 'artifact' && $slot->item->type !== 'trinket' && $slot->item->type !== 'quest';

                $hasSuffixOrPrefix = !is_null($slot->item->item_suffix_id) || !is_null($slot->item->item_prefix_id);

                $hasHolyStacks = $slot->item->holy_stacks_applied > 0;

                $hasSocketCount = $slot->item->socket_count > 0;

                return $itemIsValid && ($hasSuffixOrPrefix || $hasHolyStacks || $hasSocketCount);
            })->pluck('item.affix_name', 'item.id')->toArray(),
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
