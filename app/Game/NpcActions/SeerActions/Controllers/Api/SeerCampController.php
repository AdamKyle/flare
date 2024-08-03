<?php

namespace App\Game\NpcActions\SeerActions\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Game\NpcActions\SeerActions\Requests\AddGemToItemRequest;
use App\Game\NpcActions\SeerActions\Requests\RemoveGemFromItemRequest;
use App\Game\NpcActions\SeerActions\Requests\ReplaceGemOnItemRequest;
use App\Game\NpcActions\SeerActions\Requests\RollItemSocketsRequest;
use App\Game\NpcActions\SeerActions\Services\SeerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SeerCampController extends Controller
{
    private SeerService $seerService;

    public function __construct(SeerService $seerService)
    {
        $this->seerService = $seerService;
    }

    public function visitCamp(Character $character): JsonResponse
    {
        return response()->json([
            'items' => $this->seerService->getItems($character),
            'gems' => $this->seerService->getGems($character),
        ]);
    }

    public function rollSockets(Character $character, RollItemSocketsRequest $request): JsonResponse
    {
        $result = $this->seerService->createSockets($character, $request->slot_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function attachGemToItem(Character $character, AddGemToItemRequest $request): JsonResponse
    {
        $result = $this->seerService->assignGemToSocket($character, $request->slot_id, $request->gem_slot_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function replaceGemOnItem(Character $character, ReplaceGemOnItemRequest $request): JsonResponse
    {
        $result = $this->seerService->replaceGem($character, $request->slot_id, $request->gem_slot_id, $request->gem_slot_to_replace);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function fetchItemsWithGems(Character $character): JsonResponse
    {
        $result = $this->seerService->fetchGemsWithItemsForRemoval($character);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function removeGemFromItem(Character $character, RemoveGemFromItemRequest $removedGemFromItemRequest): JsonResponse
    {
        $result = $this->seerService->removeGem($character, $removedGemFromItemRequest->slot_id, $removedGemFromItemRequest->gem_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function removeAllGemsFromItem(Character $character, InventorySlot $inventorySlot): JsonResponse
    {
        $result = $this->seerService->removeAllGems($character, $inventorySlot->id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
