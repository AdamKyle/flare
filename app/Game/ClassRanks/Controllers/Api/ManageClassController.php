<?php

namespace App\Game\ClassRanks\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Game\ClassRanks\Services\ManageClassService;

class ManageClassController extends Controller {

    private ManageClassService $manageClassService;

    public function __construct(ManageClassService $manageClassService) {
        $this->manageClassService = $manageClassService;
    }

    public function switchClass(Character $character, GameClass $gameClass) {
        $response = $this->manageClassService->switchClass($character, $gameClass);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }
}
