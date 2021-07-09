<?php

namespace App\Game\Quests\Controllers;

use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Models\User;
use App\Http\Controllers\Controller;

class QuestsController extends Controller {

    public function index(User $user) {
        return view('game.quests.completed_quests', [
            'character' => $user->character
        ]);
    }

    public function show(Character $character, QuestsCompleted $questsCompleted) {
        $skill = null;

        if ($questsCompleted->quest->unlocks_skill) {
            $skill = GameSkill::where('type', $questsCompleted->quest->unlocks_skill_type)->where('is_locked', true)->first();
            $skill = $character->skills()->where('game_skill_id', $skill->id)->first();
        }

        return view('admin.quests.show', [
            'quest'       => $questsCompleted->quest,
            'character'   => $character,
            'lockedSkill' => $skill,
        ]);
    }
}
