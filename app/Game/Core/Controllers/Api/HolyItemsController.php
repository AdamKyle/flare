<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Core\Requests\ApplyHolyOilRequest;
use App\Game\Core\Services\HolyItemService;
use App\Http\Controllers\Controller;

class HolyItemsController extends Controller {

    private $holyItemService;

    public function __construct(HolyItemService $holyItemService) {
        $this->holyItemService = $holyItemService;
    }

    public function index(Character $character) {
        return response()->json($this->holyItemService->fetchSmithingItems($character));
    }

    public function apply(ApplyHolyOilRequest $request, Character $character) {
        return response()->json($this->holyItemService->applyOil($character, $request->all()));
    }
}
