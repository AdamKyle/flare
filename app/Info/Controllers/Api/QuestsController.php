<?php

namespace App\Info\Controllers\Api;

use App\Flare\Models\Quest;
use App\Game\Quests\Services\QuestReadService;
use App\Http\Controllers\Controller;
use App\Info\Requests\QuestTreeRequest;
use Illuminate\Http\JsonResponse;

class QuestsController extends Controller
{
    /**
     * @param  QuestReadService  $questReadService  Shared factual Quest read service.
     */
    public function __construct(
        private readonly QuestReadService $questReadService,
    ) {}

    /**
     * Return the public, read-only factual Quest tree.
     *
     * @param  QuestTreeRequest  $request  Validated Quest tree request.
     * @return JsonResponse Quest tree JSON response.
     */
    public function tree(QuestTreeRequest $request): JsonResponse
    {
        return response()->json([
            'quests' => $this->questReadService->tree($request->mapId(), $request->kind()),
        ], 200);
    }

    /**
     * Return the public, read-only full factual detail representation for the given Quest.
     *
     * @param  Quest  $quest  Quest to transform.
     * @return JsonResponse Quest detail JSON response.
     */
    public function show(Quest $quest): JsonResponse
    {
        return response()->json($this->questReadService->detail($quest), 200);
    }
}
