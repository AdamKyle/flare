<?php

namespace App\Admin\GameMaps\Controllers\Api;

use App\Admin\GameMaps\Requests\GameMapIndexRequest;
use App\Admin\GameMaps\Requests\StoreGameMapRequest;
use App\Admin\GameMaps\Requests\UpdateGameMapRequest;
use App\Admin\GameMaps\Services\GameMapService;
use App\Admin\GameMaps\Transformers\GameMapDetailTransformer;
use App\Admin\GameMaps\Transformers\GameMapEditorTransformer;
use App\Admin\GameMaps\Transformers\GameMapFormOptionsTransformer;
use App\Admin\GameMaps\Transformers\GameMapFormTransformer;
use App\Admin\GameMaps\Transformers\GameMapListTransformer;
use App\Flare\Models\GameMap;
use App\Flare\Pagination\Pagination;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GameMapsController extends Controller
{
    /**
     * @param  GameMapService  $gameMapService  Admin Game Map application service.
     * @param  Pagination  $pagination  Paginator response transformer.
     * @param  GameMapListTransformer  $gameMapListTransformer  List-record transformer.
     * @param  GameMapDetailTransformer  $gameMapDetailTransformer  Detail transformer.
     * @param  GameMapEditorTransformer  $gameMapEditorTransformer  Editor transformer.
     * @param  GameMapFormOptionsTransformer  $gameMapFormOptionsTransformer  Form-options transformer.
     * @param  GameMapFormTransformer  $gameMapFormTransformer  Form-value transformer.
     */
    public function __construct(
        private readonly GameMapService $gameMapService,
        private readonly Pagination $pagination,
        private readonly GameMapListTransformer $gameMapListTransformer,
        private readonly GameMapDetailTransformer $gameMapDetailTransformer,
        private readonly GameMapEditorTransformer $gameMapEditorTransformer,
        private readonly GameMapFormOptionsTransformer $gameMapFormOptionsTransformer,
        private readonly GameMapFormTransformer $gameMapFormTransformer,
    ) {}

    /**
     * Return the paginated, searchable, sortable Game Maps list.
     *
     * @param  GameMapIndexRequest  $request  Validated Game Map list request.
     * @return JsonResponse Paginated Game Map list JSON response.
     */
    public function index(GameMapIndexRequest $request): JsonResponse
    {
        $paginator = $this->gameMapService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->gameMapListTransformer)
        );
    }

    /**
     * Return the Admin Game Map form options.
     *
     * @return JsonResponse Game Map form-options JSON response.
     */
    public function options(): JsonResponse
    {
        $formOptions = $this->gameMapService->formOptions();

        return response()->json($this->gameMapFormOptionsTransformer->transform($formOptions), 200);
    }

    /**
     * Return the Admin detail representation for the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map to transform.
     * @return JsonResponse Game Map detail JSON response.
     */
    public function show(GameMap $gameMap): JsonResponse
    {
        $detailData = $this->gameMapService->detailData($gameMap);

        return response()->json($this->gameMapDetailTransformer->transform($detailData), 200);
    }

    /**
     * Return the Game Map editor data for the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map to edit.
     * @return JsonResponse Game Map editor JSON response.
     */
    public function editor(GameMap $gameMap): JsonResponse
    {
        $editorData = $this->gameMapService->editorData($gameMap);

        return response()->json($this->gameMapEditorTransformer->transform($editorData), 200);
    }

    /**
     * Return the current field values for the given Game Map, for populating the edit form.
     *
     * @param  GameMap  $gameMap  Game Map to populate.
     * @return JsonResponse Game Map form-value JSON response.
     */
    public function edit(GameMap $gameMap): JsonResponse
    {
        return response()->json($this->gameMapFormTransformer->transform($gameMap), 200);
    }

    /**
     * Create a new Game Map from the validated request.
     *
     * @param  StoreGameMapRequest  $request  Validated Game Map creation request.
     * @return JsonResponse Created Game Map JSON response.
     */
    public function store(StoreGameMapRequest $request): JsonResponse
    {
        $gameMap = $this->gameMapService->create($request->validated(), $request->file('map'));

        return response()->json($this->gameMapFormTransformer->transform($gameMap), 201);
    }

    /**
     * Update an existing Game Map from the validated request.
     *
     * @param  UpdateGameMapRequest  $request  Validated Game Map update request.
     * @param  GameMap  $gameMap  Game Map to update.
     * @return JsonResponse Updated Game Map JSON response.
     */
    public function update(UpdateGameMapRequest $request, GameMap $gameMap): JsonResponse
    {
        $gameMap = $this->gameMapService->update($gameMap, $request->validated(), $request->file('map'));

        return response()->json($this->gameMapFormTransformer->transform($gameMap), 200);
    }
}
