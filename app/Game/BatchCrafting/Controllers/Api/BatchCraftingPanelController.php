<?php

namespace App\Game\BatchCrafting\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use Illuminate\Http\JsonResponse;

class BatchCraftingPanelController
{
    public function __construct(private readonly BatchCraftingService $batchCraftingService) {}

    public function status(Character $character): JsonResponse
    {
        return response()->json($this->batchCraftingService->status($character));
    }

    public function dismiss(Character $character): JsonResponse
    {
        $this->batchCraftingService->dismiss($character);

        return response()->json([
            'message' => 'Batch crafting panel dismissed.',
        ]);
    }
}
