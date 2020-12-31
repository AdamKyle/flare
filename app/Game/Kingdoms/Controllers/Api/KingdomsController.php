<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Requests\KingdomsLocationRequest;
use App\Game\Kingdoms\Requests\KingdomsSettleRequest;
use App\Game\Kingdoms\Service\KingdomService;
use App\Http\Controllers\Controller;
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
}
