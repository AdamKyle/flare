<?php

namespace App\Info\Controllers\Api;

use App\Flare\Models\Monster;
use App\Game\Monsters\Services\MonsterReadService;
use App\Http\Controllers\Controller;
use App\Info\Requests\MonsterGemEffectContextIndexRequest;
use Illuminate\Http\JsonResponse;

class MonstersController extends Controller
{
    public function __construct(
        private readonly MonsterReadService $monsterReadService,
    ) {}

    /**
     * Return the public, read-only full factual detail representation for the given Monster.
     */
    public function show(Monster $monster): JsonResponse
    {
        return response()->json($this->monsterReadService->detail($monster), 200);
    }

    /**
     * Return the public, read-only append-paginated Gem effect context list for the given Monster.
     */
    public function gemEffectContexts(MonsterGemEffectContextIndexRequest $request, Monster $monster): JsonResponse
    {
        return response()->json(
            $this->monsterReadService->gemEffectContexts(
                $monster,
                $request->integer('per_page'),
                $request->integer('page'),
            ),
            200
        );
    }
}
