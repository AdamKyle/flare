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

        $this->questHandler->npcQuestsHandler()->handleNpcQuest($character, $quest);

        event(new GlobalMessageEvent($character->name . ' Has completed a quest ('.$quest->name.') for: ' . $quest->npc->real_name . ' and been rewarded with a godly gift!'));

        $character = $character->refresh();

        return response()->json([
            'completed_quests' => $character->questsCompleted()->pluck('quest_id'),
            'player_plane'     => $character->map->gameMap->name,
        ]);
    }
}
