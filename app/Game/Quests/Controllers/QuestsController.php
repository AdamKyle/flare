<?php

namespace App\Game\Quests\Controllers;

use App\Flare\Models\Adventure;
use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;
use App\Http\Controllers\Controller;

class QuestsController extends Controller {

    public function index() {
        return view('game.quests.completed_quests');
    }

    public function show(QuestsCompleted $questsCompleted) {
        return view('admin.quests.show', [
            'quest' => $questsCompleted->quest,
        ]);
    }
}
