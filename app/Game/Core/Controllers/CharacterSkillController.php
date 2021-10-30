<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Character;
use App\Flare\Models\Quest;
use App\Flare\Models\Skill;
use App\Http\Controllers\Controller;
use App\Game\Core\Requests\TrainSkillValidation;

class CharacterSkillController extends Controller {

    public function __construct() {
        $this->middleware('auth');

        $this->middleware('is.character.dead')->only([
            'train'
        ]);
        $this->middleware('is.character.adventuring')->only([
            'train'
        ]);
    }

    public function show(Skill $skill) {
        $quest = Quest::where('unlocks_skill_type', $skill->baseSkill->type)->first();

        return view('game.character.skill', [
            'skill' => $skill,
            'quest' => $quest,
        ]);
    }

}
