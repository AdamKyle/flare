<?php

namespace App\Admin\Npcs\Controllers\Api;

use App\Admin\Npcs\Requests\MoveNpcRequest;
use App\Admin\Npcs\Requests\StoreNpcRequest;
use App\Admin\Npcs\Services\NpcService;
use App\Admin\Npcs\Transformers\NpcFormOptionsTransformer;
use App\Admin\Npcs\Transformers\NpcTransformer;
use App\Flare\Models\GameMap;
use App\Flare\Models\Npc;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class NpcsController extends Controller
{
    public function __construct(
        private readonly NpcService $npcService,
        private readonly NpcTransformer $npcTransformer,
        private readonly NpcFormOptionsTransformer $npcFormOptionsTransformer,
    ) {}

    /**
     * Return the Admin Npc form options for the given Game Map.
     */
    public function options(GameMap $gameMap): JsonResponse
    {
        $formOptions = $this->npcService->formOptions($gameMap);

        return response()->json($this->npcFormOptionsTransformer->transform($formOptions), 200);
    }

    /**
     * Return the Admin representation of a single Npc on the given Game Map.
     */
    public function show(GameMap $gameMap, Npc $npc): JsonResponse
    {
        $npc = $this->npcService->findOnMap($gameMap, $npc);

        return response()->json($this->npcTransformer->transform($npc), 200);
    }

    /**
     * Create a new Npc on the given Game Map.
     */
    public function store(StoreNpcRequest $request, GameMap $gameMap): JsonResponse
    {
        $npc = $this->npcService->create($gameMap, $request);

        return response()->json($this->npcTransformer->transform($npc), 201);
    }

    /**
     * Update an existing Npc on the given Game Map.
     */
    public function update(StoreNpcRequest $request, GameMap $gameMap, Npc $npc): JsonResponse
    {
        $npc = $this->npcService->update($gameMap, $npc, $request);

        return response()->json($this->npcTransformer->transform($npc), 200);
    }

    /**
     * Move an existing Npc on the given Game Map to a new X/Y coordinate.
     */
    public function move(MoveNpcRequest $request, GameMap $gameMap, Npc $npc): JsonResponse
    {
        $npc = $this->npcService->move($gameMap, $npc, $request);

        return response()->json($this->npcTransformer->transform($npc), 200);
    }
}
