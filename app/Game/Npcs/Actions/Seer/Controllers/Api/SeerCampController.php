<?php

namespace App\Game\Npcs\Actions\Seer\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Pagination\Requests\PaginationRequest;
use App\Game\Npcs\Actions\Seer\Requests\AddGemToItemRequest;
use App\Game\Npcs\Actions\Seer\Requests\RemoveGemFromItemRequest;
use App\Game\Npcs\Actions\Seer\Requests\ReplaceGemOnItemRequest;
use App\Game\Npcs\Actions\Seer\Requests\RollItemSocketsRequest;
use App\Game\Npcs\Actions\Seer\Requests\SeerItemsRequest;
use App\Game\Npcs\Actions\Seer\Services\SeerService;
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
            'costs' => $this->seerService->getCosts(),
        ]);
    }

    public function items(SeerItemsRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->seerService->fetchPaginatedItems($character, $request->purpose, $request->per_page, $request->page, $request->search_text)
        );
    }

    public function gems(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->seerService->fetchPaginatedGems($character, $request->per_page, $request->page, $request->search_text)
        );
    }

    public function itemsWithGems(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->seerService->fetchPaginatedItemsWithGems($character, $request->per_page, $request->page, $request->search_text)
        );
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
