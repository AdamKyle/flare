<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Building;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Requests\KingdomsLocationRequest;
use App\Game\Kingdoms\Requests\KingdomsSettleRequest;
use App\Game\Kingdoms\Requests\UpgradeBuildingRequest;
use App\Game\Kingdoms\Service\BuildingService;
use App\Game\Kingdoms\Service\KingdomService;
use App\Http\Controllers\Controller;
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

        $kingdom = $kingdomService->createKingdom($character);

        $kingdom  = new Item($kingdom, $this->kingdom);

        $kingdom = $this->manager->createData($kingdom)->toArray();

        event(new AddKingdomToMap($character->user, $kingdom));

        return response()->json($kingdom, 200);
    }

    public function upgradeBuilding(Request $request, Character $character, Building $building, BuildingService $buildingService) {
        $kingdom = $building->kingdom;

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
}
