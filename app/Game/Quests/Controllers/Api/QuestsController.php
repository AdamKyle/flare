<?php

namespace App\Game\Quests\Controllers\Api;

use Cache;
use App\Flare\Models\Character;
use App\Flare\Models\Quest;
use App\Http\Controllers\Controller;

class QuestsController extends Controller {

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
}
