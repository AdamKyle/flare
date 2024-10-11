<?php

namespace App\Game\Skills\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Flare\Models\Item;
use App\Game\Skills\Services\DisenchantService;
use App\Http\Controllers\Controller;

class DisenchantingController extends Controller
{
    public function __construct(private DisenchantService $disenchantService) {}

    /**
     * @param Item $item
     * @return JsonResponse
     */
    public function disenchant(Item $item): JsonResponse
    {
        $result = $this->disenchantService->disenchantItem(auth()->user()->character, $item);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
