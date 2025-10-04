<?php

namespace App\Game\GuideQuests\Controllers\Api;

use App\Flare\Models\GuideQuest;
use App\Flare\Models\User;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GuideQuestsController extends Controller
{
    private GuideQuestService $guideQuestService;

    public function __construct(GuideQuestService $guideQuestService)
    {
        $this->guideQuestService = $guideQuestService;
    }

    public function getCurrentQuest(User $user): JsonResponse
    {
        return response()->json([
            ...$this->guideQuestService->fetchQuestForCharacter($user->character),
        ]);
    }

    public function handInQuest(User $user, GuideQuest $guideQuest): JsonResponse
    {
        $character = $user->character;
        $response = $this->guideQuestService->handInQuest($character, $guideQuest);

        $message = 'You have completed the quest: "'.$guideQuest->name.'". On to the next! Below is the next quest for you to do!';

        if ($response) {
            return response()->json([
                'message' => $message,
                ...$this->guideQuestService->fetchQuestForCharacter($character),
            ]);
        }

        return response()->json([
            'message' => 'You cannot hand in this guide quest. You must meet all the requirements first.',
        ], 422);
    }
}
