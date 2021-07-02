<?php

namespace App\Game\Quests\Controllers;

use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;
use App\Http\Controllers\Controller;

class QuestsController extends Controller {

    public function index(Character $character) {
        return view('game.quests.completed_quests', [
            'character' => $character
        ]);
    }

    public function show(Character $character, QuestsCompleted $questsCompleted) {
        return view('admin.quests.show', [
            'quest'     => $questsCompleted->quest,
            'character' => $character,
        ]);
    }
}
