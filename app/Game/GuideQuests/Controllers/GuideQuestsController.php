<?php

namespace App\Game\GuideQuests\Controllers;


use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\User;
use App\Http\Controllers\Controller;

class GuideQuestsController extends Controller {

    public function index(User $user) {
        return view('game.guide-quests.completed-quests', [
            'character' => $user->character
        ]);
    }

    public function show(Character $character, GuideQuest $guideQuest) {
        return view('admin.guide-quests.show', ['guideQuest' => $guideQuest]);
    }

}
