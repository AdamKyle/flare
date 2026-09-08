<?php

namespace App\Admin\Quests\Controllers\Api;

use App\Admin\Quests\Requests\StoreQuestRequest;
use App\Admin\Quests\Requests\UpdateQuestRequest;
use App\Admin\Quests\Services\QuestService;
use App\Admin\Quests\Transformers\QuestFormOptionsTransformer;
use App\Admin\Quests\Transformers\QuestFormTransformer;
use App\Flare\Models\Quest;
use App\Game\Quests\Requests\QuestTreeRequest;
use App\Game\Quests\Services\QuestReadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class QuestsController extends Controller
{
    public function __construct(
        private readonly QuestService $questService,
        private readonly QuestReadService $questReadService,
        private readonly QuestFormTransformer $questFormTransformer,
        private readonly QuestFormOptionsTransformer $questFormOptionsTransformer,
    ) {}

    /**
     * Return the factual, optionally Map- and Kind-filtered Quest tree.
     */
    public function tree(QuestTreeRequest $request): JsonResponse
    {
        return response()->json([
            'quests' => $this->questReadService->tree($request->mapId(), $request->kind()),
        ], 200);
    }

    /**
     * Return the Admin Quest form options.
     */
    public function options(): JsonResponse
    {
        return response()->json(
            $this->questFormOptionsTransformer->transform($this->questService->formOptions()),
            200
        );
    }

    /**
     * Return the factual Quest browse options for the Admin Quest browser.
     */
    public function browseOptions(): JsonResponse
    {
        return response()->json($this->questReadService->browseOptions(), 200);
    }

    /**
     * Return the full factual detail representation for the given Quest.
     */
    public function show(Quest $quest): JsonResponse
    {
        return response()->json($this->questReadService->detail($quest), 200);
    }

    /**
     * Return the current field values for the given Quest, for populating the edit form.
     */
    public function edit(Quest $quest): JsonResponse
    {
        return response()->json($this->questFormTransformer->transform($quest), 200);
    }

    /**
     * Create a new Quest from the validated request.
     */
    public function store(StoreQuestRequest $request): JsonResponse
    {
        $quest = $this->questService->create($request);

        return response()->json($this->questFormTransformer->transform($quest), 201);
    }

    /**
     * Update an existing Quest from the validated request.
     */
    public function update(UpdateQuestRequest $request, Quest $quest): JsonResponse
    {
        $quest = $this->questService->update($quest, $request);

        return response()->json($this->questFormTransformer->transform($quest), 200);
    }
}
