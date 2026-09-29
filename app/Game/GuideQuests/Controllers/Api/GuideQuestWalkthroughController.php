<?php

namespace App\Game\GuideQuests\Controllers\Api;

use App\Flare\Models\GuideQuest;
use App\Flare\Models\GuideQuestWalkthroughStep;
use App\Flare\Models\User;
use App\Game\GuideQuests\Requests\GuideQuestWalkthroughProgressRequest;
use App\Game\GuideQuests\Services\GuideQuestWalkthroughService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GuideQuestWalkthroughController extends Controller
{
    /**
     * @param GuideQuestWalkthroughService $guideQuestWalkthroughService
     */
    public function __construct(
        private readonly GuideQuestWalkthroughService $guideQuestWalkthroughService,
    ) {}

    /**
     * Return an applicable Guide Quest's ordered walkthrough on demand.
     *
     * @param User $user
     * @param GuideQuest $guideQuest
     * @return JsonResponse
     */
    public function show(User $user, GuideQuest $guideQuest): JsonResponse
    {
        $walkthrough = $this->guideQuestWalkthroughService->fetch($user->character, $guideQuest);

        if (is_null($walkthrough)) {
            return response()->json(['message' => 'This walkthrough is not available.'], 404);
        }

        return response()->json($walkthrough);
    }

    /**
     * Persist one Character's visual walkthrough progress.
     *
     * @param GuideQuestWalkthroughProgressRequest $request
     * @param User $user
     * @param GuideQuest $guideQuest
     * @return JsonResponse
     */
    public function update(
        GuideQuestWalkthroughProgressRequest $request,
        User $user,
        GuideQuest $guideQuest,
    ): JsonResponse {
        $step = GuideQuestWalkthroughStep::find($request->validated('step_id'));
        $updated = $this->guideQuestWalkthroughService->updateProgress(
            $user->character,
            $guideQuest,
            $step,
            $request->validated('completed'),
        );

        if (! $updated) {
            return response()->json(['message' => 'This walkthrough progress cannot be updated.'], 422);
        }

        return response()->json(['message' => 'Walkthrough progress saved.']);
    }
}
