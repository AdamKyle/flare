<?php

namespace App\Admin\Npcs\Controllers\Api;

use App\Admin\Npcs\Requests\MoveNpcRequest;
use App\Admin\Npcs\Requests\NpcIndexRequest;
use App\Admin\Npcs\Requests\NpcRelationIndexRequest;
use App\Admin\Npcs\Requests\StoreNpcRequest;
use App\Admin\Npcs\Services\NpcService;
use App\Admin\Npcs\Transformers\NpcDetailTransformer;
use App\Admin\Npcs\Transformers\NpcFormOptionsTransformer;
use App\Admin\Npcs\Transformers\NpcListTransformer;
use App\Admin\Npcs\Transformers\NpcQuestTransformer;
use App\Admin\Npcs\Transformers\NpcTransformer;
use App\Flare\Models\GameMap;
use App\Flare\Models\Npc;
use App\Flare\Pagination\Pagination;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class NpcsController extends Controller
{
    /**
     * @param  NpcService  $npcService  Canonical NPC application service.
     * @param  NpcTransformer  $npcTransformer  NPC transformer used by map-scoped routes.
     * @param  NpcFormOptionsTransformer  $npcFormOptionsTransformer  NPC form-options transformer.
     * @param  NpcListTransformer  $npcListTransformer  Standalone NPC list transformer.
     * @param  NpcDetailTransformer  $npcDetailTransformer  Standalone NPC detail transformer.
     * @param  NpcQuestTransformer  $npcQuestTransformer  NPC Quest relationship transformer.
     * @param  QuestItemTransformer  $questItemTransformer  Canonical quest Item transformer.
     * @param  Pagination  $pagination  Paginator response transformer.
     */
    public function __construct(
        private readonly NpcService $npcService,
        private readonly NpcTransformer $npcTransformer,
        private readonly NpcFormOptionsTransformer $npcFormOptionsTransformer,
        private readonly NpcListTransformer $npcListTransformer,
        private readonly NpcDetailTransformer $npcDetailTransformer,
        private readonly NpcQuestTransformer $npcQuestTransformer,
        private readonly QuestItemTransformer $questItemTransformer,
        private readonly Pagination $pagination,
    ) {}

    /**
     * Return the paginated, searchable, sortable standalone NPCs list.
     *
     * @param  NpcIndexRequest  $request  Validated NPC list request.
     * @return JsonResponse Paginated NPC list JSON response.
     */
    public function index(NpcIndexRequest $request): JsonResponse
    {
        $paginator = $this->npcService->paginate($request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->npcListTransformer)
        );
    }

    /**
     * Return the standalone Admin detail representation for the given NPC.
     *
     * @param  Npc  $npc  NPC to transform.
     * @return JsonResponse NPC detail JSON response.
     */
    public function showNpc(Npc $npc): JsonResponse
    {
        $detailData = $this->npcService->detailData($npc);

        return response()->json($this->npcDetailTransformer->transform($detailData), 200);
    }

    /**
     * Return the paginated Quests belonging to the given NPC.
     *
     * @param  NpcRelationIndexRequest  $request  Validated relationship list request.
     * @param  Npc  $npc  NPC whose Quests are being listed.
     * @return JsonResponse Paginated Quests JSON response.
     */
    public function quests(NpcRelationIndexRequest $request, Npc $npc): JsonResponse
    {
        $paginator = $this->npcService->paginateQuests($npc, $request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->npcQuestTransformer)
        );
    }

    /**
     * Return the paginated, deduplicated quest-reward Items awarded by the given NPC's Quests.
     *
     * @param  NpcRelationIndexRequest  $request  Validated relationship list request.
     * @param  Npc  $npc  NPC whose reward Items are being listed.
     * @return JsonResponse Paginated reward Items JSON response.
     */
    public function rewardItems(NpcRelationIndexRequest $request, Npc $npc): JsonResponse
    {
        $paginator = $this->npcService->paginateRewardItems($npc, $request);

        return response()->json(
            $this->pagination->transformLengthAwarePaginator($paginator, $this->questItemTransformer)
        );
    }

    /**
     * Return the Admin Npc form options for the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map the NPC form belongs to.
     * @return JsonResponse NPC form-options JSON response.
     */
    public function options(GameMap $gameMap): JsonResponse
    {
        $formOptions = $this->npcService->formOptions($gameMap);

        return response()->json($this->npcFormOptionsTransformer->transform($formOptions), 200);
    }

    /**
     * Return the Admin representation of a single Npc on the given Game Map.
     *
     * @param  GameMap  $gameMap  Game Map the NPC is expected to belong to.
     * @param  Npc  $npc  NPC to transform.
     * @return JsonResponse NPC JSON response.
     */
    public function show(GameMap $gameMap, Npc $npc): JsonResponse
    {
        $npc = $this->npcService->findOnMap($gameMap, $npc);

        return response()->json($this->npcTransformer->transform($npc), 200);
    }

    /**
     * Create a new Npc on the given Game Map.
     *
     * @param  StoreNpcRequest  $request  Validated NPC creation request.
     * @param  GameMap  $gameMap  Game Map the new NPC belongs to.
     * @return JsonResponse Created NPC JSON response.
     */
    public function store(StoreNpcRequest $request, GameMap $gameMap): JsonResponse
    {
        $npc = $this->npcService->create($gameMap, $request);

        return response()->json($this->npcTransformer->transform($npc), 201);
    }

    /**
     * Update an existing Npc on the given Game Map.
     *
     * @param  StoreNpcRequest  $request  Validated NPC update request.
     * @param  GameMap  $gameMap  Game Map the NPC belongs to.
     * @param  Npc  $npc  NPC to update.
     * @return JsonResponse Updated NPC JSON response.
     */
    public function update(StoreNpcRequest $request, GameMap $gameMap, Npc $npc): JsonResponse
    {
        $npc = $this->npcService->update($gameMap, $npc, $request);

        return response()->json($this->npcTransformer->transform($npc), 200);
    }

    /**
     * Move an existing Npc on the given Game Map to a new X/Y coordinate.
     *
     * @param  MoveNpcRequest  $request  Validated NPC move request.
     * @param  GameMap  $gameMap  Game Map the NPC belongs to.
     * @param  Npc  $npc  NPC to move.
     * @return JsonResponse Moved NPC JSON response.
     */
    public function move(MoveNpcRequest $request, GameMap $gameMap, Npc $npc): JsonResponse
    {
        $npc = $this->npcService->move($gameMap, $npc, $request);

        return response()->json($this->npcTransformer->transform($npc), 200);
    }
}
