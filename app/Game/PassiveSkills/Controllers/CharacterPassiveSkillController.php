<?php

namespace App\Game\PassiveSkills\Controllers;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\PassiveSkill;
use App\Http\Controllers\Controller;

class CharacterPassiveSkillController extends Controller
{
    public function viewSkill(CharacterPassiveSkill $characterPassiveSkill, Character $character)
    {
        return view('game.passive-skills.skill', [
            'skill' => $characterPassiveSkill,
            'character' => $character,
        ]);
    }

    public function viewCharacterPassiveSkill(PassiveSkill $passiveSkill, Character $character)
    {
        $skill = $character->passiveSkills()->where('passive_skill_id', $passiveSkill->id)->first();

        return redirect()->to(route('view.passive.skill', [
            'characterPassiveSkill' => $skill->id,
            'character' => $character->id,
        ]));
    }
}
