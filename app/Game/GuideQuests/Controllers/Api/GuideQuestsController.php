<?php

namespace App\Game\GuideQuests\Controllers\Api;


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

        $quest = $this->guideQuestService->fetchQuestForCharacter($user->character);

        if (!is_null($quest)) {

            $quest->intro_text   = nl2br($quest->intro_text);
            $quest->instructions = nl2br($quest->instructions);

            return response()->json([
                'quest'       => $quest,
                'can_hand_in' => $this->guideQuestService->canHandInQuest($user->character, $quest),
            ]);
        }

        return response()->json([
            'error' => 'Could not find next quest.'
        ], 422);
    }

}
