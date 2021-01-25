<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Building;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Models\UnitInQueue;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Game\Kingdoms\Requests\KingdomsLocationRequest;
use App\Game\Kingdoms\Requests\KingdomsSettleRequest;
use App\Game\Kingdoms\Service\BuildingService;
use App\Game\Kingdoms\Service\KingdomService;
use App\Game\Kingdoms\Service\UnitService;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class KingdomsController extends Controller {

    private $manager;

    private $kingdom;

    public function __construct(Manager $manager, KingdomTransformer $kingdom) {
        $this->middleware('auth:api');
        $this->middleware('is.character.dead');;

        $this->manager = $manager;
        $this->kingdom = $kingdom;
    }

    public function getLocationData(KingdomsLocationRequest $request) {
        $kingdom = Kingdom::where('x_position', $request->x_position)->where('y_position', $request->y_position)->first();
        
        if (is_null($kingdom)) {
            $location = Location::where('x', $request->x_position)->where('y', $request->y_position)->first();

            if (!is_null($location)) {
                return response()->json(['cannot_settle' => true]);
            }
            
            return response()->json([], 200);
        }
        
        
        $kingdom  = new Item($kingdom, $this->kingdom);

        return response()->json(
            $this->manager->createData($kingdom)->toArray(),
            200
        );
    }

    public function settle(KingdomsSettleRequest $request, Character $character, KingdomService $kingdomService) {
        $kingdomService->setParams($request->all());

        if (!$kingdomService->canSettle($request->x_position, $request->y_position)) {
            return response()->json([
                'message' => 'Cannot settle here.'
            ], 422);
        }
        
        $kingdom = $kingdomService->createKingdom($character);

        return response()->json(
            $kingdomService->addKingdomToMap(
                $character, $kingdom, $this->kingdom, $this->manager
            ), 
        200);
    }

    public function upgradeBuilding(Request $request, Character $character, Building $building, BuildingService $buildingService) {

        if (ResourceValidation::shouldRedirectBuilding($building, $building->kingdom)) {
            return response()->json([
                'message' => "You don't have the resources."
            ], 422);
        }

        $kingdom = $buildingService->updateKingdomResourcesForBuildingUpgrade($building);
        
        $buildingService->upgradeBuilding($building, $character);

        $kingdom  = new Item($kingdom, $this->kingdom);

        $kingdom  = $this->manager->createData($kingdom)->toArray();

        return response()->json($kingdom, 200);
    }

    public function recruitUnits(Request $request, Kingdom $kingdom, GameUnit $gameUnit, UnitService $service) {
        $request->validate([
            'amount' => 'required|integer',
        ]);

        if ($request->amount <= 0) {
            return response()->json([
                'message' => "Too few units to recuit."
            ], 422);
        }

        if (ResourceValidation::shouldRedirectUnits($gameUnit, $kingdom, $request->amount)) {
            return response()->json([
                'message' => "You don't have the resources."
            ], 422);
        }

        $service->updateKingdomResources($kingdom, $gameUnit, $request->amount);

        $service->recruitUnits($kingdom, $gameUnit, $request->amount);

        $kingdom  = new Item($kingdom, $this->kingdom);

        $kingdom  = $this->manager->createData($kingdom)->toArray();

        return response()->json($kingdom, 200);
    }

    public function cancelRecruit(Request $request, UnitService $service) {
        $request->validate([
            'queue_id' => 'required|integer',
        ]);

        $queue = UnitInQueue::find($request->queue_id);

        if (is_null($queue)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
        }

        $cancelled = $service->cancelRecruit($queue, $this->manager, $this->kingdom);

        if (!$cancelled) {
            return response()->json([
                'message' => 'Your units are almost done. You can\'t cancel this late in the process.'
            ], 422);
        }
        
        return response()->json([], 200);
    }

    public function removeBuildingFromQueue(Request $request, BuildingService $service) {

        $request->validate([
            'queue_id' => 'required|integer',
        ]);

        $queue = BuildingInQueue::find($request->queue_id);

        if (is_null($queue)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
        }
        
        $canceled = $service->cancelBuildingUpgrade($queue, $this->manager, $this->kingdom);

        if (!$canceled) {
            return response()->json([
                'message' => 'Your workers are almost done. You can\'t cancel this late in the process.'
            ], 422);
        }

        return response()->json([], 200);
    }
}
