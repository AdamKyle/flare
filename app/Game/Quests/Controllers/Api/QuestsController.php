<?php

namespace App\Game\Quests\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Quest;
use App\Http\Controllers\Controller;

class QuestsController extends Controller {

    public function index(Character $character) {

        $quests = Quest::where('is_parent', true)->with('childQuests')->get()->where('belongs_to_map_name', $character->map->gameMap->name);

        return response()->json([
            'completed_quests' => $character->questsCompleted()->pluck('quest_id'),
            'quests'           => $quests->values()
        ]);
    }

    public function quest(Quest $quest, Character $character) {
        return response()->json($quest->loadRelations());
    }
}
