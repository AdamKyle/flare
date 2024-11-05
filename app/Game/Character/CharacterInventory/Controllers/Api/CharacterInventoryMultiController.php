<?php

namespace App\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Character\CharacterInventory\Requests\InventoryMultiRequest;
use App\Game\Character\CharacterInventory\Requests\MoveSelectedItemsRequest;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CharacterInventoryMultiController extends Controller
{
    public function __construct(private readonly MultiInventoryActionService $multiInventoryActionService) {}

    public function equipSelected(InventoryMultiRequest $request, Character $character)
    {

        $result = $this->multiInventoryActionService->equipManyItems($character, $request->slot_ids);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function moveSelected(MoveSelectedItemsRequest $request, Character $character): JsonResponse
    {
        $result = $this->multiInventoryActionService->moveManyItemsToSelectedSet($character, $request->set_id, $request->slot_ids);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function destroySelected(InventoryMultiRequest $request, Character $character)
    {
        $result = $this->multiInventoryActionService->destroyManyItems($character, $request->slot_ids);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function disenchantSelected(InventoryMultiRequest $request, Character $character)
    {
        $result = $this->multiInventoryActionService->disenchantManyItems($character, $request->slot_ids);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function sellSelected(InventoryMultiRequest $request, Character $character)
    {
        $result = $this->multiInventoryActionService->sellManyItems($character, $request->slot_ids);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
