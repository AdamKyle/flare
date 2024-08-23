<?php

namespace App\Game\GuideQuests\Controllers\Api;

use App\Flare\Models\Character;
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
        return $this->getNextQuest($user->character);
    }

    public function handInQuest(User $user, GuideQuest $guideQuest): JsonResponse
    {
        $character = $user->character;
        $response = $this->guideQuestService->handInQuest($character, $guideQuest);

        $message = 'You have completed the quest: "'.$guideQuest->name.'". On to the next! Below is the next quest for you to do!';

        if ($response) {
            return $this->getNextQuest($character->refresh(), $message);
        }

        return response()->json([
            'message' => 'Oh christ, something is wrong. Quick call The Creator! Submit a bug report indicating the guide quest failed to load.',
        ], 422);
    }

    /**
     * Get the next guide quest.
     */
    protected function getNextQuest(Character $character, string $message = ''): JsonResponse
    {
        $data = $this->guideQuestService->fetchQuestForCharacter($character);


        return response()->json([...$data, 'message' => $message]);
    }
}
