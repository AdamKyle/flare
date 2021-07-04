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

    public function train(TrainSkillValidation $request, Character $character) {
        // Find the skill we want to train.
        $skill = $character->skills->filter(function ($skill) use($request) {
            return $skill->id === (int) $request->skill_id;
        })->first();

        if (is_null($skill)) {
            return redirect()->back()->with('error', 'Invalid Input.');
        }

        $skillCurrentlyTraining = $character->skills->filter(function($skill) {
            return $skill->currently_training;
        })->first();

        if (!is_null($skillCurrentlyTraining)) {
            $skillCurrentlyTraining->update([
                'currently_training' => false,
                'xp_twoards'         => 0.0,
            ]);
        }

        // Begin training
        $skill->update([
            'currently_training' => true,
            'xp_towards'         => $request->xp_percentage,
            'xp_max'             => is_null($skill->xp_max) ? rand(100, 150) : $skill->xp_max,
        ]);

        return redirect()->back()->with('success', 'You are now training ' . $skill->name);
    }

    public function show(Skill $skill) {
        $quest = Quest::where('unlocks_skill_type', $skill->baseSkill->type)->first();

        return view('game.character.skill', [
            'skill' => $skill,
            'quest' => $quest,
        ]);
    }

    public function cancelTrain(Skill $skill) {
        $skill->update([
            'currently_training' => false,
            'xp_towards'         => 0.0,
        ]);

        return redirect()->back()->with('success', 'You stopped training: ' . $skill->name);
    }
}
