<?php

namespace App\Game\Npcs\Actions\WorkBench\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Npcs\Actions\WorkBench\Requests\ApplyHolyOilRequest;
use App\Game\Npcs\Actions\WorkBench\Services\HolyItemService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HolyItemsController extends Controller
{
    private HolyItemService $holyItemService;

    public function __construct(HolyItemService $holyItemService)
    {
        $this->holyItemService = $holyItemService;
    }

    public function index(Character $character): JsonResponse
    {
        return response()->json($this->holyItemService->fetchSmithingItems($character));
    }

    public function apply(ApplyHolyOilRequest $request, Character $character): JsonResponse
    {

        $response = $this->holyItemService->applyOil($character, $request->all());

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }
}
