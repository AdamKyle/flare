<?php

namespace App\Game\Quests\Controllers\Api;

use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Quests\Services\QuestHandlerService;
use Cache;
use App\Flare\Models\Character;
use App\Flare\Models\Quest;
use App\Http\Controllers\Controller;

class QuestsController extends Controller {

    private $questHandler;

    public function __construct(QuestHandlerService $questHandlerService) {
        $this->questHandler = $questHandlerService;
    }

    public function index(Character $character) {

        $quests = Quest::where('is_parent', true)->with('childQuests')->get();

        return response()->json([
            'completed_quests' => $character->questsCompleted()->pluck('quest_id'),
            'quests'           => $quests->toArray(),
            'player_plane'     => $character->map->gameMap->name,
        ]);
    }

    public function quest(Quest $quest, Character $character) {
        return response()->json($quest->loadRelations());
    }

    public function handInQuest(Quest $quest, Character $character) {
        if ($this->questHandler->shouldBailOnQuest($character, $quest)) {
            return response()->json([
                'message' => $this->questHandler->getBailMessage()
            ], 422);
        }

        $characterIsAtLocation = $character->map()
                                           ->where('x_position', $quest->npc->x_position)
                                           ->where('y_position', $quest->npc->y_position)
                                           ->where('game_map_id', $quest->npc->game_map_id);

        if (!is_null($characterIsAtLocation)) {

            $response = $this->questHandler->moveCharacter($character, $quest->npc);

            if ($response instanceof Character) {
                $response = $this->questHandler->handInQuest($character, $quest);
            }
        } else {
            $response = $this->questHandler->handInQuest($character, $quest);
        }

        if ($response['status'] === 422) {
            unset($response['status']);

            return response()->json($response, 422);
        }

        unset($response['status']);

        return response()->json($response);


    }
}
