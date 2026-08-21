<?php

namespace App\Game\Automation\BatchCrafting\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Requests\BatchCraftingRequest;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingAutomationService;
use App\Game\Automation\BatchCrafting\Services\CraftSetHandRecommendationService;
use App\Game\Automation\BatchCrafting\Services\CraftSetRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchCraftingController
{
    /**
     * @param  BatchCraftingAutomationService  $batchCraftingAutomationService
     * @param  CraftSetRecommendationService  $craftSetRecommendationService
     * @param  CraftSetHandRecommendationService  $craftSetHandRecommendationService
     */
    public function __construct(
        private readonly BatchCraftingAutomationService $batchCraftingAutomationService,
        private readonly CraftSetRecommendationService $craftSetRecommendationService,
        private readonly CraftSetHandRecommendationService $craftSetHandRecommendationService,
    ) {}

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
     * Return the authoritative Craft Set recommendation for the character.
     *
     * @param  Character  $character  The character requesting the recommendation.
     * @return JsonResponse The recommended Craft Set positions and any missing required positions.
     */
    public function craftSetRecommendation(Character $character): JsonResponse
    {
        return response()->json($this->craftSetRecommendationService->build($character));
    }

    /**
     * Recommend the best currently craftable item for one explicitly selected Craft Set hand type.
     *
     * @param  Request  $request  The incoming hand recommendation request.
     * @param  Character  $character  The character requesting the recommendation.
     * @return JsonResponse The recommended hand item, or a null item when nothing is craftable for the hand type.
     */
    public function craftSetHandRecommendation(Request $request, Character $character): JsonResponse
    {
        $handType = $request->string('hand_type')->toString();

        $item = $this->craftSetHandRecommendationService->recommend($character, $handType);

        if (is_null($item)) {
            return response()->json(['item' => null]);
        }

        return response()->json([
            'item' => [
                'item_id' => $item->id,
                'item_name' => $item->affix_name ?? $item->name,
            ],
        ]);
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
