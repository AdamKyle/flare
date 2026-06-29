<?php

namespace App\Game\BatchCrafting\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use Illuminate\Http\JsonResponse;

class BatchCraftingInfoController
{
    public function __construct(private readonly BatchCraftingService $batchCraftingService) {}

    public function acknowledge(Character $character): JsonResponse
    {
        $this->batchCraftingService->acknowledgeInfo($character);

        return response()->json([
            'message' => 'Batch crafting information acknowledged.',
        ]);
    }
}
