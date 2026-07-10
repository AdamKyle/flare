<?php

namespace App\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Character\CharacterInventory\Requests\DestroyAllFromSetRequest;
use App\Game\Character\CharacterInventory\Requests\InventoryMultiRequest;
use App\Game\Character\CharacterInventory\Requests\MoveSelectedItemsRequest;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CharacterInventoryMultiController extends Controller
{
    use ChecksAutomationRestrictions;

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
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->multiInventoryActionService->destroyManyItems($character, $request->slot_ids);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function disenchantSelected(InventoryMultiRequest $request, Character $character)
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->multiInventoryActionService->disenchantManyItems($character, $request->slot_ids);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function sellSelected(InventoryMultiRequest $request, Character $character)
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->multiInventoryActionService->sellManyItems($character, $request->slot_ids);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function sellSelectedFromSet(MoveSelectedItemsRequest $request, Character $character)
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $set = InventorySet::find($request->set_id);

        if (is_null($set)) {
            return response()->json(['message' => 'Cannot do that.'], 422);
        }

        $result = $this->multiInventoryActionService->sellManySetSlots($character, $set, $request->slot_ids);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function disenchantSelectedFromSet(MoveSelectedItemsRequest $request, Character $character)
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $set = InventorySet::find($request->set_id);

        if (is_null($set)) {
            return response()->json(['message' => 'Cannot do that.'], 422);
        }

        $result = $this->multiInventoryActionService->disenchantManySetSlots($character, $set, $request->slot_ids);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function destroySelectedFromSet(MoveSelectedItemsRequest $request, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $set = InventorySet::find($request->set_id);

        if (is_null($set)) {
            return response()->json(['message' => 'Cannot do that.'], 422);
        }

        $result = $this->multiInventoryActionService->destroyManySetSlots($character, $set, $request->slot_ids);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function destroyAllFromSet(DestroyAllFromSetRequest $request, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $set = InventorySet::find($request->set_id);

        if (is_null($set)) {
            return response()->json(['message' => 'Cannot do that.'], 422);
        }

        $result = $this->multiInventoryActionService->destroyAllCraftedItemsSetSlots($character, $set);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
