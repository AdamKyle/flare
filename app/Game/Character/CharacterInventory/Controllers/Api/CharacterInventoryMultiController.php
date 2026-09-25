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

    /**
     * @param MultiInventoryActionService $multiInventoryActionService
     */
    public function __construct(private readonly MultiInventoryActionService $multiInventoryActionService) {}

    /**
     * Equip the selected inventory slots unless an automation blocks equipment management.
     *
     * @param InventoryMultiRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function equipSelected(InventoryMultiRequest $request, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::EQUIPMENT_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->multiInventoryActionService->equipManyItems($character, $request->slot_ids);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Move the selected inventory slots into the chosen Inventory Set.
     *
     * @param MoveSelectedItemsRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function moveSelected(MoveSelectedItemsRequest $request, Character $character): JsonResponse
    {
        $result = $this->multiInventoryActionService->moveManyItemsToSelectedSet($character, $request->set_id, $request->slot_ids);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Destroy the selected inventory slots unless an automation blocks inventory management.
     *
     * @param InventoryMultiRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function destroySelected(InventoryMultiRequest $request, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->multiInventoryActionService->destroyManyItems($character, $request->all());

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Disenchant the selected inventory slots unless an automation blocks inventory management.
     *
     * @param InventoryMultiRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function disenchantSelected(InventoryMultiRequest $request, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->multiInventoryActionService->disenchantManyItems($character, $request->all());

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Sell the selected inventory slots unless an automation blocks inventory management.
     *
     * @param InventoryMultiRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function sellSelected(InventoryMultiRequest $request, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->multiInventoryActionService->sellManyItems($character, $request->all());

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Sell the selected Inventory Set slots unless an automation blocks inventory management.
     *
     * @param MoveSelectedItemsRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function sellSelectedFromSet(MoveSelectedItemsRequest $request, Character $character): JsonResponse
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

    /**
     * Disenchant the selected Inventory Set slots unless an automation blocks inventory management.
     *
     * @param MoveSelectedItemsRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function disenchantSelectedFromSet(MoveSelectedItemsRequest $request, Character $character): JsonResponse
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

    /**
     * Destroy the selected Inventory Set slots unless an automation blocks inventory management.
     *
     * @param MoveSelectedItemsRequest $request
     * @param Character $character
     * @return JsonResponse
     */
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

    /**
     * Destroy every crafted item in an Inventory Set unless an automation blocks inventory management.
     *
     * @param DestroyAllFromSetRequest $request
     * @param Character $character
     * @return JsonResponse
     */
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

    /**
     * Sell every crafted item in an Inventory Set unless an automation blocks inventory management.
     *
     * @param DestroyAllFromSetRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function sellAllFromSet(DestroyAllFromSetRequest $request, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $set = InventorySet::find($request->set_id);

        if (is_null($set)) {
            return response()->json(['message' => 'Cannot do that.'], 422);
        }

        $result = $this->multiInventoryActionService->sellAllCraftedItemsSetSlots($character, $set);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Disenchant every crafted item in an Inventory Set unless an automation blocks inventory management.
     *
     * @param DestroyAllFromSetRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function disenchantAllFromSet(DestroyAllFromSetRequest $request, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $set = InventorySet::find($request->set_id);

        if (is_null($set)) {
            return response()->json(['message' => 'Cannot do that.'], 422);
        }

        $result = $this->multiInventoryActionService->disenchantAllCraftedItemsSetSlots($character, $set);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
