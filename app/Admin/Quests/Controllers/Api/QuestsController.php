<?php

namespace App\Admin\Quests\Controllers\Api;

use App\Admin\Quests\Requests\QuestTreeRequest;
use App\Admin\Quests\Requests\StoreQuestRequest;
use App\Admin\Quests\Requests\UpdateQuestRequest;
use App\Admin\Quests\Services\QuestService;
use App\Admin\Quests\Transformers\QuestFormOptionsTransformer;
use App\Admin\Quests\Transformers\QuestFormTransformer;
use App\Flare\Models\Quest;
use App\Game\Quests\Services\QuestReadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class QuestsController extends Controller
{
    /**
     * @param  QuestService  $questService  Admin Quest mutation service.
     * @param  QuestReadService  $questReadService  Shared factual Quest read service.
     * @param  QuestFormTransformer  $questFormTransformer  Form-value transformer.
     * @param  QuestFormOptionsTransformer  $questFormOptionsTransformer  Form-options transformer.
     */
    public function __construct(
        private readonly QuestService $questService,
        private readonly QuestReadService $questReadService,
        private readonly QuestFormTransformer $questFormTransformer,
        private readonly QuestFormOptionsTransformer $questFormOptionsTransformer,
    ) {}

    /**
     * Return the factual, optionally Map- and Kind-filtered Quest tree.
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
     * Return the Admin Quest form options.
     *
     * @return JsonResponse Quest form-options JSON response.
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
     *
     * @return JsonResponse Quest browse-options JSON response.
     */
    public function browseOptions(): JsonResponse
    {
        return response()->json($this->questReadService->browseOptions(), 200);
    }

    /**
     * Return the full factual detail representation for the given Quest.
     *
     * @param  Quest  $quest  Quest to transform.
     * @return JsonResponse Quest detail JSON response.
     */
    public function show(Quest $quest): JsonResponse
    {
        return response()->json($this->questReadService->detail($quest), 200);
    }

    /**
     * Return the current field values for the given Quest, for populating the edit form.
     *
     * @param  Quest  $quest  Quest to populate.
     * @return JsonResponse Quest form-value JSON response.
     */
    public function edit(Quest $quest): JsonResponse
    {
        return response()->json($this->questFormTransformer->transform($quest), 200);
    }

    /**
     * Create a new Quest from the validated request.
     *
     * @param  StoreQuestRequest  $request  Validated Quest creation request.
     * @return JsonResponse Created Quest JSON response.
     */
    public function store(StoreQuestRequest $request): JsonResponse
    {
        $quest = $this->questService->create($request);

        return response()->json($this->questFormTransformer->transform($quest), 201);
    }

    /**
     * Update an existing Quest from the validated request.
     *
     * @param  UpdateQuestRequest  $request  Validated Quest update request.
     * @param  Quest  $quest  Quest to update.
     * @return JsonResponse Updated Quest JSON response.
     */
    public function update(UpdateQuestRequest $request, Quest $quest): JsonResponse
    {
        $quest = $this->questService->update($quest, $request);

        return response()->json($this->questFormTransformer->transform($quest), 200);
    }
}
