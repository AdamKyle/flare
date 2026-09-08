<?php

namespace App\Admin\GameMaps\Controllers\Api;

use App\Admin\GameMaps\Requests\GameMapIndexRequest;
use App\Admin\GameMaps\Requests\GameMapRelationIndexRequest;
use App\Admin\GameMaps\Requests\StoreGameMapRequest;
use App\Admin\GameMaps\Requests\UpdateGameMapRequest;
use App\Admin\GameMaps\Services\GameMapService;
use App\Admin\GameMaps\Transformers\GameMapDetailTransformer;
use App\Admin\GameMaps\Transformers\GameMapEditorTransformer;
use App\Admin\GameMaps\Transformers\GameMapFormOptionsTransformer;
use App\Admin\GameMaps\Transformers\GameMapFormTransformer;
use App\Admin\GameMaps\Transformers\GameMapListTransformer;
use App\Admin\GameMaps\Transformers\GameMapRelatedLocationTransformer;
use App\Admin\GameMaps\Transformers\GameMapRelatedMonsterTransformer;
use App\Admin\GameMaps\Transformers\GameMapRelatedNpcTransformer;
use App\Admin\GameMaps\Transformers\GameMapRelatedQuestTransformer;
use App\Flare\Models\GameMap;
use App\Flare\Pagination\Pagination;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GameMapsController extends Controller
{
    public function __construct(
        private readonly GameMapService $gameMapService,
        private readonly Pagination $pagination,
        private readonly GameMapListTransformer $gameMapListTransformer,
        private readonly GameMapDetailTransformer $gameMapDetailTransformer,
        private readonly GameMapEditorTransformer $gameMapEditorTransformer,
        private readonly GameMapFormOptionsTransformer $gameMapFormOptionsTransformer,
        private readonly GameMapFormTransformer $gameMapFormTransformer,
        private readonly GameMapRelatedLocationTransformer $gameMapRelatedLocationTransformer,
        private readonly GameMapRelatedNpcTransformer $gameMapRelatedNpcTransformer,
        private readonly GameMapRelatedMonsterTransformer $gameMapRelatedMonsterTransformer,
        private readonly GameMapRelatedQuestTransformer $gameMapRelatedQuestTransformer,
        private readonly QuestItemTransformer $questItemTransformer,
    ) {}

    /**
     * Return the paginated, searchable, sortable Game Maps list.
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
     */
    public function options(): JsonResponse
    {
        $formOptions = $this->gameMapService->formOptions();

        return response()->json($this->gameMapFormOptionsTransformer->transform($formOptions), 200);
    }

    /**
     * Return the Admin detail representation for the given Game Map.
     */
    public function show(GameMap $gameMap): JsonResponse
    {
        $detailData = $this->gameMapService->detailData($gameMap);

        return response()->json($this->gameMapDetailTransformer->transform($detailData), 200);
    }

    /**
     * Return the Game Map editor data for the given Game Map.
     */
    public function editor(GameMap $gameMap): JsonResponse
    {
        $editorData = $this->gameMapService->editorData($gameMap);

        return response()->json($this->gameMapEditorTransformer->transform($editorData), 200);
    }

    /**
     * Return the current field values for the given Game Map, for populating the edit form.
     */
    public function edit(GameMap $gameMap): JsonResponse
    {
        return response()->json($this->gameMapFormTransformer->transform($gameMap), 200);
    }

    /**
     * Return the paginated Locations belonging to the given Game Map.
     */
    public function relatedLocations(GameMapRelationIndexRequest $request, GameMap $gameMap): JsonResponse
    {
        $paginator = $this->gameMapService->paginateRelatedLocations($gameMap, $request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->gameMapRelatedLocationTransformer)
        );
    }

    /**
     * Return the paginated NPCs belonging to the given Game Map.
     */
    public function relatedNpcs(GameMapRelationIndexRequest $request, GameMap $gameMap): JsonResponse
    {
        $paginator = $this->gameMapService->paginateRelatedNpcs($gameMap, $request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->gameMapRelatedNpcTransformer)
        );
    }

    /**
     * Return the paginated Monsters available on the given Game Map.
     */
    public function relatedMonsters(GameMapRelationIndexRequest $request, GameMap $gameMap): JsonResponse
    {
        $paginator = $this->gameMapService->paginateRelatedMonsters($gameMap, $request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->gameMapRelatedMonsterTransformer)
        );
    }

    /**
     * Return the paginated Quests whose Quest-giver NPC belongs to the given Game Map.
     */
    public function relatedQuests(GameMapRelationIndexRequest $request, GameMap $gameMap): JsonResponse
    {
        $paginator = $this->gameMapService->paginateRelatedQuests($gameMap, $request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->gameMapRelatedQuestTransformer)
        );
    }

    /**
     * Return the paginated, deduplicated quest Items connected to the given Game Map.
     */
    public function relatedQuestItems(GameMapRelationIndexRequest $request, GameMap $gameMap): JsonResponse
    {
        $paginator = $this->gameMapService->paginateRelatedQuestItems($gameMap, $request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->questItemTransformer)
        );
    }

    /**
     * Create a new Game Map from the validated request.
     */
    public function store(StoreGameMapRequest $request): JsonResponse
    {
        $gameMap = $this->gameMapService->create($request->validated(), $request->file('map'));

        return response()->json($this->gameMapFormTransformer->transform($gameMap), 201);
    }

    /**
     * Update an existing Game Map from the validated request.
     */
    public function update(UpdateGameMapRequest $request, GameMap $gameMap): JsonResponse
    {
        $gameMap = $this->gameMapService->update($gameMap, $request->validated(), $request->file('map'));

        return response()->json($this->gameMapFormTransformer->transform($gameMap), 200);
    }
}
