<?php

namespace App\Info\Controllers\Api;

use App\Flare\Models\Quest;
use App\Game\Quests\Requests\QuestTreeRequest;
use App\Game\Quests\Services\QuestReadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class QuestsController extends Controller
{
    public function __construct(
        private readonly QuestReadService $questReadService,
    ) {}

    /**
     * Return the public, read-only factual Quest tree.
     */
    public function tree(QuestTreeRequest $request): JsonResponse
    {
        return response()->json([
            'quests' => $this->questReadService->tree($request->mapId(), $request->kind()),
        ], 200);
    }

    /**
     * Return the public, read-only factual Quest browse options.
     */
    public function options(): JsonResponse
    {
        return response()->json($this->questReadService->browseOptions(), 200);
    }

    /**
     * Return the public, read-only full factual detail representation for the given Quest.
     */
    public function show(Quest $quest): JsonResponse
    {
        return response()->json($this->questReadService->detail($quest), 200);
    }
}
