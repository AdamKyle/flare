<?php

namespace App\Admin\Locations\Controllers\Api;

use App\Admin\Locations\Requests\MoveLocationRequest;
use App\Admin\Locations\Requests\StoreLocationRequest;
use App\Admin\Locations\Services\LocationService;
use App\Admin\Locations\Transformers\LocationFormOptionsTransformer;
use App\Admin\Locations\Transformers\LocationTransformer;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LocationsController extends Controller
{
    public function __construct(
        private readonly LocationService $locationService,
        private readonly LocationTransformer $locationTransformer,
        private readonly LocationFormOptionsTransformer $locationFormOptionsTransformer,
    ) {}

    /**
     * Return the Admin Location form options for the given Game Map.
     */
    public function options(GameMap $gameMap): JsonResponse
    {
        $formOptions = $this->locationService->formOptions($gameMap);

        return response()->json($this->locationFormOptionsTransformer->transform($formOptions), 200);
    }

    /**
     * Return the Admin representation of a single Location on the given Game Map.
     */
    public function show(GameMap $gameMap, Location $location): JsonResponse
    {
        $location = $this->locationService->findOnMap($gameMap, $location);

        return response()->json($this->locationTransformer->transform($location), 200);
    }

    /**
     * Create a new Location on the given Game Map.
     */
    public function store(StoreLocationRequest $request, GameMap $gameMap): JsonResponse
    {
        $location = $this->locationService->create($gameMap, $request);

        return response()->json($this->locationTransformer->transform($location), 201);
    }

    /**
     * Update an existing Location on the given Game Map.
     */
    public function update(StoreLocationRequest $request, GameMap $gameMap, Location $location): JsonResponse
    {
        $location = $this->locationService->update($gameMap, $location, $request);

        return response()->json($this->locationTransformer->transform($location), 200);
    }

    /**
     * Move an existing Location on the given Game Map to a new X/Y coordinate.
     */
    public function move(MoveLocationRequest $request, GameMap $gameMap, Location $location): JsonResponse
    {
        $location = $this->locationService->move($gameMap, $location, $request);

        return response()->json($this->locationTransformer->transform($location), 200);
    }
}
