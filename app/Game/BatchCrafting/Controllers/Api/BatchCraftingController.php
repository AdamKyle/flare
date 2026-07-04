<?php

namespace App\Game\BatchCrafting\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\BatchCrafting\Requests\BatchCraftingPreviewRequest;
use App\Game\BatchCrafting\Requests\BatchCraftingStartRequest;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use Illuminate\Http\JsonResponse;

class BatchCraftingController
{
    public function __construct(private readonly BatchCraftingService $batchCraftingService) {}

    public function start(BatchCraftingStartRequest $request, Character $character): JsonResponse
    {
        $batchCrafting = $this->batchCraftingService->start($character, $request->validated());

        return response()->json([
            'message' => 'Batch crafting has started.',
            'batch_crafting_id' => $batchCrafting->id,
        ]);
    }

    public function preview(BatchCraftingPreviewRequest $request, Character $character): JsonResponse
    {
        $preview = $this->batchCraftingService->preview($character, $request->validated());

        return response()->json($preview);
    }

    public function cancel(Character $character): JsonResponse
    {
        $this->batchCraftingService->cancel($character);

        return response()->json([
            'message' => 'Batch crafting has been cancelled.',
        ]);
    }
}
