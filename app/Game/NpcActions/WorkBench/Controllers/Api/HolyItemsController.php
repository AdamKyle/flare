<?php

namespace App\Game\NpcActions\WorkBench\Controllers\Api;

use App\Flare\Models\Character;
use App\Http\Controllers\Controller;
use App\Game\NpcActions\WorkBench\Services\HolyItemService;
use App\Game\NpcActions\WorkBench\Requests\ApplyHolyOilRequest;

class HolyItemsController extends Controller {

    private $holyItemService;

    public function __construct(HolyItemService $holyItemService) {
        $this->holyItemService = $holyItemService;
    }

    public function index(Character $character) {
        return response()->json($this->holyItemService->fetchSmithingItems($character));
    }

    public function apply(ApplyHolyOilRequest $request, Character $character) {

        $response = $this->holyItemService->applyOil($character, $request->all());

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }
}
