<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Game\Core\Requests\AddGemToItemRequest;
use App\Game\Core\Requests\ComparisonFromChatValidate;
use App\Game\Core\Requests\ComparisonValidation;
use App\Game\Core\Requests\RemoveGemFromItemRequest;
use App\Game\Core\Requests\ReplaceGemOnItemRequest;
use App\Game\Core\Requests\RollItemSocketsRequest;
use App\Game\Core\Services\CharacterGemBagService;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Core\Services\ComparisonService;
use App\Game\Core\Services\SeerService;
use App\Game\Skills\Services\GemService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SeerCampController extends Controller {

    private SeerService $seerService;

    public function __construct(SeerService $seerService) {
        $this->seerService = $seerService;
    }

    public function visitCamp(Character $character): JsonResponse {
        return response()->json([
            'items' => $this->seerService->getItems($character),
            'gems' => $this->seerService->getGems($character),
        ]);
    }

    public function rollSockets(Character $character, RollItemSocketsRequest $request): JsonResponse {
        $result = $this->seerService->createSockets($character, $request->slot_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function attachGemToItem(Character $character, AddGemToItemRequest $request): JsonResponse {
        $result = $this->seerService->assignGemToSocket($character, $request->slot_id, $request->gem_slot_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function replaceGemOnItem(Character $character, ReplaceGemOnItemRequest $request): JsonResponse {
        $result = $this->seerService->replaceGem($character, $request->slot_id, $request->gem_slot_id, $request->gem_slot_to_replace);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function fetchItemsWithGems(Character $character): JsonResponse {
        $result = $this->seerService->fetchGemsWithItemsForRemoval($character);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function removeGemFromItem(Character $character, RemoveGemFromItemRequest $removedGemFromItemRequest): JsonResponse {
        $result = $this->seerService->removeGem($character, $removedGemFromItemRequest->slot_id, $removedGemFromItemRequest->gem_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function removeAllGemsFromItem(Character $character, InventorySlot $inventorySlot): JsonResponse {
        $result = $this->seerService->removeAllGems($character, $inventorySlot);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
