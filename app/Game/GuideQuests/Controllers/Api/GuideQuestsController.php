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
            'message' => 'Oh christ, something is wrong. Quick call The Creator!',
        ]);
    }

    /**
     * Get the next guide quest.
     */
    protected function getNextQuest(Character $character, string $message = ''): JsonResponse
    {
        $data = $this->guideQuestService->fetchQuestForCharacter($character);

        if (! is_null($data)) {

            $quest = $data['quest'];

            $quest->intro_text = nl2br($quest->intro_text);
            $quest->instructions = nl2br($quest->instructions);
            $quest->desktop_instructions = nl2br($quest->desktop_instructions);
            $quest->mobile_instructions = nl2br($quest->mobile_instructions);

            $response = [
                'quest' => $quest,
                'can_hand_in' => $data['can_hand_in'],
                'completed_requirements' => $data['completed_requirements'],
            ];

            if ($message !== '') {
                $response['message'] = $message;
            }

            return response()->json($response);
        }

        $response = [
            'quest' => null,
            'can_hand_in' => false,
            'completed_requirements' => [],
        ];

        return response()->json($response);
    }
}
