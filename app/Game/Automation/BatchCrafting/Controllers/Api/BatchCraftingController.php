<?php

namespace App\Game\Automation\BatchCrafting\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Requests\BatchCraftingRequest;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingAutomationService;
use Illuminate\Http\JsonResponse;

class BatchCraftingController
{
    /**
     * @param  BatchCraftingAutomationService  $batchCraftingAutomationService  The Batch Crafting lifecycle service.
     */
    public function __construct(private readonly BatchCraftingAutomationService $batchCraftingAutomationService) {}

    /**
     * Start a new Batch Crafting run for the character.
     *
     * @param  BatchCraftingRequest  $request  The validated Batch Crafting start request.
     * @param  Character  $character  The character starting the run.
     * @return JsonResponse The lifecycle service's start result.
     */
    public function start(BatchCraftingRequest $request, Character $character): JsonResponse
    {
        return $this->respond($this->batchCraftingAutomationService->start($character, $request->validated()));
    }

    /**
     * Preview the Craft Amount request before starting a Batch Crafting run.
     *
     * @param  BatchCraftingRequest  $request  The validated Batch Crafting preview request.
     * @param  Character  $character  The character requesting the preview.
     * @return JsonResponse The Craft Amount preview payload.
     */
    public function preview(BatchCraftingRequest $request, Character $character): JsonResponse
    {
        return $this->respond($this->batchCraftingAutomationService->preview($character, $request->validated()));
    }

    /**
     * Cancel the character's currently running Batch Crafting run.
     *
     * @param  Character  $character  The character cancelling the run.
     * @return JsonResponse The lifecycle service's cancel result.
     */
    public function cancel(Character $character): JsonResponse
    {
        return $this->respond($this->batchCraftingAutomationService->cancel($character));
    }

    /**
     * Return the character's current Batch Crafting panel status.
     *
     * @param  Character  $character  The character requesting status.
     * @return JsonResponse The current Batch Crafting panel status.
     */
    public function status(Character $character): JsonResponse
    {
        return $this->respond($this->batchCraftingAutomationService->status($character));
    }

    /**
     * Dismiss the character's finished Batch Crafting panel.
     *
     * @param  Character  $character  The character dismissing the panel.
     * @return JsonResponse The lifecycle service's dismiss result.
     */
    public function dismiss(Character $character): JsonResponse
    {
        return $this->respond($this->batchCraftingAutomationService->dismiss($character));
    }

    /**
     * Acknowledge the Batch Crafting introduction for the character.
     *
     * @param  Character  $character  The character acknowledging the introduction.
     * @return JsonResponse The lifecycle service's acknowledgement result.
     */
    public function acknowledgeInfo(Character $character): JsonResponse
    {
        return $this->respond($this->batchCraftingAutomationService->acknowledgeInfo($character));
    }

    /**
     * Convert a ResponseBuilder service result into a JSON response.
     *
     * @param  array  $response  The service result containing a status key.
     * @return JsonResponse The response with the status key applied as the HTTP status.
     */
    private function respond(array $response): JsonResponse
    {
        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }
}
