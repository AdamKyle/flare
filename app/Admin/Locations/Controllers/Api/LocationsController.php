<?php

namespace App\Admin\Locations\Controllers\Api;

use App\Admin\Locations\Requests\LocationIndexRequest;
use App\Admin\Locations\Requests\LocationQuestItemIndexRequest;
use App\Admin\Locations\Requests\MoveLocationRequest;
use App\Admin\Locations\Requests\StoreLocationRequest;
use App\Admin\Locations\Services\LocationService;
use App\Admin\Locations\Transformers\LocationDetailTransformer;
use App\Admin\Locations\Transformers\LocationFormOptionsTransformer;
use App\Admin\Locations\Transformers\LocationListTransformer;
use App\Admin\Locations\Transformers\LocationTransformer;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Pagination\Pagination;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Maps\Values\LocationType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LocationsController extends Controller
{
    /**
     * @param  LocationService  $locationService  Canonical Location application service.
     * @param  LocationTransformer  $locationTransformer  Location transformer used by map-scoped routes.
     * @param  LocationFormOptionsTransformer  $locationFormOptionsTransformer  Location form-options transformer.
     * @param  LocationListTransformer  $locationListTransformer  Standalone Location list transformer.
     * @param  LocationDetailTransformer  $locationDetailTransformer  Standalone Location detail transformer.
     * @param  QuestItemTransformer  $questItemTransformer  Canonical quest Item transformer.
     * @param  Pagination  $pagination  Paginator response transformer.
     */
    public function __construct(
        private readonly LocationService $locationService,
        private readonly LocationTransformer $locationTransformer,
        private readonly LocationFormOptionsTransformer $locationFormOptionsTransformer,
        private readonly LocationListTransformer $locationListTransformer,
        private readonly LocationDetailTransformer $locationDetailTransformer,
        private readonly QuestItemTransformer $questItemTransformer,
        private readonly Pagination $pagination,
    ) {}

    /**
     * Return the paginated, searchable, sortable standalone Locations list.
     *
     * @param  LocationIndexRequest  $request  Validated Location list request.
     * @return JsonResponse Paginated Location list JSON response.
     */
    public function index(LocationIndexRequest $request): JsonResponse
    {
        $paginator = $this->locationService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->locationListTransformer)
        );
    }

    /**
     * Return the standalone Admin detail representation for the given Location.
     *
     * @param  Location  $location  Location to transform.
     * @return JsonResponse Location detail JSON response.
     */
    public function showLocation(Location $location): JsonResponse
    {
        $detailData = $this->locationService->detailData($location);

        return response()->json($this->locationDetailTransformer->transform($detailData), 200);
    }

    /**
     * Return the paginated quest Items dropped at the given Location.
     *
     * @param  LocationQuestItemIndexRequest  $request  Validated quest-Item list request.
     * @param  Location  $location  Location whose quest-Item drops are being listed.
     * @return JsonResponse Paginated quest-Item drops JSON response.
     */
    public function questItems(LocationQuestItemIndexRequest $request, Location $location): JsonResponse
    {
        $paginator = $this->locationService->paginateQuestItemDrops($location, $request);

        $response = $this->pagination->transformLengthAwarePaginator($paginator, $this->questItemTransformer);
        $locationType = is_null($location->type) ? null : LocationType::from($location->type);

        $response['meta']['location_drop_mode'] = [
            'manual_fighting_only' => $locationType?->canDropManualQuestItems() ?? false,
            'is_cave_of_memories' => $locationType?->isCaveOfMemories() ?? false,
        ];

        return response()->json($response, 200);
    }

    /**
     * Return the Admin Location form options for the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map the Location form belongs to.
     * @return JsonResponse Location form-options JSON response.
     */
    public function options(GameMap $gameMap): JsonResponse
    {
        $formOptions = $this->locationService->formOptions($gameMap);

        return response()->json($this->locationFormOptionsTransformer->transform($formOptions), 200);
    }

    /**
     * Return the Admin representation of a single Location on the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map the Location is expected to belong to.
     * @param  Location  $location  Location to transform.
     * @return JsonResponse Location JSON response.
     */
    public function show(GameMap $gameMap, Location $location): JsonResponse
    {
        $location = $this->locationService->findOnMap($gameMap, $location);

        return response()->json($this->locationTransformer->transform($location), 200);
    }

    /**
     * Create a new Location on the given Game Map.
     *
     * @param  StoreLocationRequest  $request  Validated Location creation request.
     * @param  GameMap  $gameMap  Game Map the new Location belongs to.
     * @return JsonResponse Created Location JSON response.
     */
    public function store(StoreLocationRequest $request, GameMap $gameMap): JsonResponse
    {
        $location = $this->locationService->create($gameMap, $request);

        return response()->json($this->locationTransformer->transform($location), 201);
    }

    /**
     * Update an existing Location on the given Game Map.
     *
     * @param  StoreLocationRequest  $request  Validated Location update request.
     * @param  GameMap  $gameMap  Game Map the Location belongs to.
     * @param  Location  $location  Location to update.
     * @return JsonResponse Updated Location JSON response.
     */
    public function update(StoreLocationRequest $request, GameMap $gameMap, Location $location): JsonResponse
    {
        $location = $this->locationService->update($gameMap, $location, $request);

        return response()->json($this->locationTransformer->transform($location), 200);
    }

    /**
     * Move an existing Location on the given Game Map to a new X/Y coordinate.
     *
     * @param  MoveLocationRequest  $request  Validated Location move request.
     * @param  GameMap  $gameMap  Game Map the Location belongs to.
     * @param  Location  $location  Location to move.
     * @return JsonResponse Moved Location JSON response.
     */
    public function move(MoveLocationRequest $request, GameMap $gameMap, Location $location): JsonResponse
    {
        $location = $this->locationService->move($gameMap, $location, $request);

        return response()->json($this->locationTransformer->transform($location), 200);
    }
}
