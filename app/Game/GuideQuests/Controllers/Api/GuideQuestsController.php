<?php

namespace App\Game\GuideQuests\Controllers\Api;

use App\Flare\Models\GuideQuest;
use App\Flare\Models\User;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GuideQuestsController extends Controller
{
    /**
     * @param GuideQuestService $guideQuestService
     */
    public function __construct(private readonly GuideQuestService $guideQuestService) {}

    /**
     * Return the user's Character's current Guide Quests with hand-in eligibility and requirement progress.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function getCurrentQuest(User $user): JsonResponse
    {
        return response()->json([
            ...$this->guideQuestService->fetchQuestForCharacter($user->character),
        ]);
    }

    /**
     * Hand in the Guide Quest for the user's Character and return the next Guide Quests, or a 422 when requirements are unmet.
     *
     * @param User $user
     * @param GuideQuest $guideQuest
     * @return JsonResponse
     */
    public function handInQuest(User $user, GuideQuest $guideQuest): JsonResponse
    {
        $character = $user->character;

        if (! $this->guideQuestService->handInQuest($character, $guideQuest)) {
            return response()->json([
                'message' => 'You cannot hand in this guide quest. You must meet all the requirements first.',
            ], 422);
        }

        return response()->json([
            'message' => 'You have completed the quest: "'.$guideQuest->name.'". On to the next! Below is the next quest for you to do!',
            ...$this->guideQuestService->fetchQuestForCharacter($character),
        ]);
    }

    /**
     * Acknowledge current Guide Quest Activity attention for the authenticated user.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function acknowledgeAttention(User $user): JsonResponse
    {
        $this->guideQuestService->acknowledgeAttention($user->character);

        return response()->json(['message' => 'Guide Quest attention acknowledged.']);
    }
}
