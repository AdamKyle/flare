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

        $kingdom = Kingdom::where('x_position', $request->x_position)->where('y_position', $request->y_position)->first();
        
        if (!is_null($kingdom)) {
            return response()->json([
                'message' => 'Cannot settle here.'
            ], 422);
        }

        $location = Location::where('x', $request->x_position)->where('y', $request->y_position)->first();
        
        if (!is_null($location)) {
            return response()->json([
                'message' => 'Cannot settle here.'
            ], 422);
        }

        $kingdom = $kingdomService->createKingdom($character);

        $kingdom  = new Item($kingdom, $this->kingdom);

        $kingdom = $this->manager->createData($kingdom)->toArray();

        event(new AddKingdomToMap($character->user, $kingdom));

        return response()->json($kingdom, 200);
    }

    public function upgradeBuilding(Request $request, Character $character, Building $building, BuildingService $buildingService) {
        $kingdom = $building->kingdom;

        if (ResourceValidation::shouldRedirectBuilding($building, $kingdom)) {
            return response()->json([
                'message' => "You don't have the resources."
            ], 422);
        }

        $kingdom->update([
            'current_wood'       => $kingdom->current_wood - $building->wood_cost,
            'current_clay'       => $kingdom->current_clay - $building->clay_cost,
            'current_stone'      => $kingdom->current_stone - $building->stone_cost,
            'current_iron'       => $kingdom->current_iron - $building->iron_cost,
            'current_population' => $kingdom->current_population - $building->required_population,
        ]);
        
        $buildingService->setBuilding($building)->upgradeBuilding($character);

        $kingdom  = $building->kingdom->refresh();

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

        $kingdom->update([
            'current_wood'       => $kingdom->current_wood - ($gameUnit->wood_cost * $request->amount),
            'current_clay'       => $kingdom->current_clay - ($gameUnit->clay_cost * $request->amount),
            'current_stone'      => $kingdom->current_stone - ($gameUnit->strone_cost * $request->amount),
            'current_iron'       => $kingdom->current_iron - ($gameUnit->iron_cost * $request->amount),
            'current_population' => $kingdom->current_population - ($gameUnit->required_population * $request->amount),
        ]);

        $kingdom = $kingdom->refresh();

        $service->setUnit($gameUnit)->setKingdom($kingdom)->recruitUnits($kingdom->character, $request->amount);

        $kingdom  = new Item($kingdom, $this->kingdom);

        $kingdom  = $this->manager->createData($kingdom)->toArray();

        return response()->json($kingdom, 200);
    }

    public function cancelRecruit(Request $request) {
        $request->validate([
            'queue_id' => 'required|integer',
        ]);

        $queue = UnitInQueue::find($request->queue_id);

        if (is_null($queue)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
        }

        $start   = Carbon::parse($queue->started_at)->timestamp;
        $end     = Carbon::parse($queue->completed_at)->timestamp;
        $current = Carbon::parse(now())->timestamp;

        $completed      = (($current - $start) / ($end - $start));
        $totalResources = 1 - $completed;
        
        if (!($totalResources >= .10)) {
            return response()->json([
                'message' => 'Your units are almost done. You can\'t cancel this late in the process.'
            ], 422);
        }

        $unit    = $queue->unit;
        $kingdom = $queue->kingdom;
        $user    = $kingdom->character->user; 

        $queue->delete();

        $kingdom->update([
            'current_wood'       => $kingdom->current_wood + (($unit->wood_cost * $queue->amount) * $totalResources),
            'current_clay'       => $kingdom->current_clay + (($unit->clay_cost * $queue->amount) * $totalResources),
            'current_stone'      => $kingdom->current_stone + (($unit->stone_cost * $queue->amount) * $totalResources),
            'current_iron'       => $kingdom->current_iron + (($unit->iron_cost * $queue->amount) * $totalResources),
            'current_population' => $kingdom->current_population + (($unit->required_population * $queue->amount) * $totalResources)
        ]);

        $kingdom  = new Item($kingdom->refresh(), $this->kingdom);

        $kingdom = $this->manager->createData($kingdom)->toArray();

        event(new UpdateKingdom($user, $kingdom));

        return response()->json([], 200);
    }

    public function removeBuildingFromQueue(Request $request) {

        $request->validate([
            'queue_id' => 'required|integer',
        ]);

        $queue = BuildingInQueue::find($request->queue_id);

        if (is_null($queue)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
        }
        
        $start   = Carbon::parse($queue->started_at)->timestamp;
        $end     = Carbon::parse($queue->completed_at)->timestamp;
        $current = Carbon::parse(now())->timestamp;

        $completed      = (($current - $start) / ($end - $start));
        $totalResources = 1 - $completed;
        
        if (!($totalResources >= .10) || $completed === 0) {
            return response()->json([
                'message' => 'Your workers are almost done. You can\'t cancel this late in the process.'
            ], 422);
        }

        $building = $queue->building;
        $kingdom  = $building->kingdom; 

        $queue->delete();

        $kingdom->update([
            'current_wood'       => $kingdom->current_wood + ($building->wood_cost * $totalResources),
            'current_clay'       => $kingdom->current_clay + ($building->clay_cost * $totalResources),
            'current_stone'      => $kingdom->current_stone + ($building->stone_cost * $totalResources),
            'current_iron'       => $kingdom->current_iron + ($building->iron_cost * $totalResources),
            'current_population' => $kingdom->current_population + ($building->required_population * $totalResources)
        ]);
        
        $kingdom = $kingdom->refresh();
        $user    = $kingdom->character->user;

        $kingdom  = new Item($kingdom, $this->kingdom);

        $kingdom = $this->manager->createData($kingdom)->toArray();

        event(new UpdateKingdom($user, $kingdom));

        return response()->json([], 200);
    }
}
