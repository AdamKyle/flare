<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Events\UpdateUnitMovementLogs;
use App\Game\Kingdoms\Service\UnitRecallService;
use App\Game\Kingdoms\Traits\UnitInMovementFormatter;
use App\Http\Controllers\Controller;

class KingdomUnitMovementController extends Controller {

    use UnitInMovementFormatter;

    private $unitRecall;

    public function __construct(UnitRecallService $unitRecall) {
        $this->unitRecall = $unitRecall;

        $this->middleware('auth:api');
    }

    public function fetchUnitMovement(Character $character) {
        $unitsInMovement = $character->unitMovementQueues()->where('is_moving', true)->get();

        return response()->json($this->format($unitsInMovement), 200);
    }

    public function recallUnits(UnitMovementQueue $unitMovementQueue, Character $character) {

        $timeLeft = $this->unitRecall->getTimeLeft($unitMovementQueue);

        $unitMovementData = $unitMovementQueue->getAttributes();

        if ($timeLeft > 0.90) {
            return response()->json([
                'message' => 'You\'re units are too close to their destination.'
            ], 200);
        }

        $unitMovementQueue->delete();

        UpdateUnitMovementLogs::dispatch($character);

        $this->unitRecall->recall($unitMovementData, $character);

        return response()->json([], 200);
    }

}
