<?php

namespace App\Game\GuideQuests\Controllers\Api;


use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Models\User;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Http\Controllers\Controller;

class GuideQuestsController extends Controller {

    private GuideQuestService $guideQuestService;

    public function __construct(GuideQuestService $guideQuestService) {
        $this->guideQuestService = $guideQuestService;
    }

    public function getCurrentQuest(User $user) {
        return $this->getNextQuest($user->character);
    }

    public function handInQuest(User $user, GuideQuest $guideQuest) {
        $character = $user->character;
        $response  = $this->guideQuestService->handInQuest($character, $guideQuest);

        $message = 'You have completed the quest: ' . $guideQuest->name . ' on to the next. Below is the next quest for you to do!';

        if ($response) {
            return $this->getNextQuest($character->refresh(), $message);
        }

        return response()->json([
            'message' => 'Oh christ, something is wrong. Quick call The Creator!'
        ]);
    }


    protected function getNextQuest(Character $character, string $message = '') {
        $quest = $this->guideQuestService->fetchQuestForCharacter($character);

        if (!is_null($quest)) {

            $quest->intro_text   = nl2br($quest->intro_text);
            $quest->instructions = nl2br($quest->instructions);

            $response =[
                'quest'       => $quest,
                'can_hand_in' => $this->guideQuestService->canHandInQuest($character, $quest),
            ];

            if ($message !== '') {
                $response['message'] = $message;
            }

            return response()->json($response);
        }

        return response()->json([
            'error' => 'Could not find next quest.'
        ], 422);
    }
}
