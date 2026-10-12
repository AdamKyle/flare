<?php

namespace App\Admin\Controllers\Api\GuideQuests;

use App\Admin\Requests\GuideQuestIndexRequest;
use App\Admin\Requests\GuideQuestRequest;
use App\Admin\Requests\GuideQuestStoreRequest;
use App\Admin\Requests\GuideQuestWalkthroughRequest;
use App\Admin\Services\GuideQuestAdminReadService;
use App\Admin\Services\GuideQuestService;
use App\Admin\Transformers\GuideQuestDetailTransformer;
use App\Admin\Transformers\GuideQuestListTransformer;
use App\Admin\Transformers\GuideQuestTransformer;
use App\Flare\Models\GuideQuest;
use App\Flare\Pagination\Pagination;
use App\Game\GuideQuests\Services\GuideQuestWalkthroughService;
use Illuminate\Http\JsonResponse;

class GuideQuestsController
{
    /**
     * @param Pagination $pagination
     * @param GuideQuestAdminReadService $guideQuestAdminReadService
     * @param GuideQuestListTransformer $guideQuestListTransformer
     * @param GuideQuestTransformer $guideQuestTransformer
     * @param GuideQuestDetailTransformer $guideQuestDetailTransformer
     * @param GuideQuestService $guideQuestService
     * @param GuideQuestWalkthroughService $guideQuestWalkthroughService
     */
    public function __construct(
        private readonly Pagination $pagination,
        private readonly GuideQuestAdminReadService $guideQuestAdminReadService,
        private readonly GuideQuestListTransformer $guideQuestListTransformer,
        private readonly GuideQuestTransformer $guideQuestTransformer,
        private readonly GuideQuestDetailTransformer $guideQuestDetailTransformer,
        private readonly GuideQuestService $guideQuestService,
        private readonly GuideQuestWalkthroughService $guideQuestWalkthroughService,
    ) {}

    /**
     * Return the paginated Admin Guide Quest list.
     *
     * @param GuideQuestIndexRequest $request
     * @return JsonResponse
     */
    public function index(GuideQuestIndexRequest $request): JsonResponse
    {
        return response()->json($this->pagination->transformLengthAwarePaginator(
            $this->guideQuestAdminReadService->paginate($request),
            $this->guideQuestListTransformer
        ));
    }

    /**
     * Return the full factual Admin Guide Quest detail.
     *
     * @param GuideQuest $guideQuest
     * @return JsonResponse
     */
    public function show(GuideQuest $guideQuest): JsonResponse
    {
        return response()->json($this->guideQuestDetailTransformer->transform($guideQuest));
    }

    /**
     * Return the Guide Quest editor data and form options.
     *
     * @param GuideQuestRequest $request
     * @return JsonResponse
     */
    public function guideQuest(GuideQuestRequest $request): JsonResponse
    {
        return response()->json(
            $this->guideQuestAdminReadService->editorData($request->validated('guide_quest_id'))
        );
    }

    /**
     * Replace a Guide Quest's ordered walkthrough steps with the validated steps.
     *
     * @param GuideQuestWalkthroughRequest $request
     * @param GuideQuest $guideQuest
     * @return JsonResponse
     */
    public function storeWalkthrough(
        GuideQuestWalkthroughRequest $request,
        GuideQuest $guideQuest,
    ): JsonResponse {
        $this->guideQuestWalkthroughService->replaceSteps($guideQuest, $request->validated('steps'));

        return response()->json([
            'steps' => $guideQuest->walkthroughSteps()->get(),
            'message' => 'Guided walkthrough saved.',
        ]);
    }

    /**
     * Persist Guide Quest editor content and return the saved detail.
     *
     * @param GuideQuestStoreRequest $guideQuestStoreRequest
     * @return JsonResponse
     */
    public function storeFormResponse(GuideQuestStoreRequest $guideQuestStoreRequest): JsonResponse
    {
        $guideQuest = $this->guideQuestService->upsert($guideQuestStoreRequest->validated(), new GuideQuest);

        return response()->json(['guide_quest' => $this->guideQuestTransformer->transform($guideQuest)]);
    }
}
