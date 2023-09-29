<?php

namespace App\Game\GuideQuests\Controllers\Api;


use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\User;
use App\Game\GuideQuests\Events\RemoveGuideQuestButton;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class GuideQuestsController extends Controller {

    /**
     * @var GuideQuestService $guideQuestService
     */
    private GuideQuestService $guideQuestService;

    /**
     * @param GuideQuestService $guideQuestService
     */
    public function __construct(GuideQuestService $guideQuestService) {
        $this->guideQuestService = $guideQuestService;
    }

    /**
     * @param User $user
     * @return JsonResponse
     */
    public function getCurrentQuest(User $user): JsonResponse {
        return $this->getNextQuest($user->character);
    }

    /**
     * @param User $user
     * @param GuideQuest $guideQuest
     * @return JsonResponse
     */
    public function handInQuest(User $user, GuideQuest $guideQuest): JsonResponse {
        $character = $user->character;
        $response  = $this->guideQuestService->handInQuest($character, $guideQuest);

        $message = 'You have completed the quest: "' . $guideQuest->name . '". On to the next! Below is the next quest for you to do!';

        if ($response) {
            return $this->getNextQuest($character->refresh(), $message);
        }

        return response()->json([
            'message' => 'Oh christ, something is wrong. Quick call The Creator!'
        ]);
    }

    /**
     * Get the next guide quest.
     *
     * @param Character $character
     * @param string $message
     * @return JsonResponse
     */
    protected function getNextQuest(Character $character, string $message = ''): JsonResponse {
        $data = $this->guideQuestService->fetchQuestForCharacter($character);

        $quest = $data['quest'];

        if (!is_null($quest)) {

            $quest->intro_text           = nl2br($quest->intro_text);
            $quest->instructions         = nl2br($quest->instructions);
            $quest->desktop_instructions = nl2br($quest->desktop_instructions);
            $quest->mobile_instructions  = nl2br($quest->mobile_instructions);

            $response = [
                'quest'                  => $quest,
                'can_hand_in'            => $data['can_hand_in'],
                'completed_requirements' => $data['completed_requirements'],
            ];

            if ($message !== '') {
                $response['message'] = $message;
            }

            return response()->json($response);
        }

        $response = [
            'quest'                  => null,
            'can_hand_in'            => false,
            'completed_requirements' => [],
        ];

        event(new RemoveGuideQuestButton($character->user));

        return response()->json($response);
    }
}
