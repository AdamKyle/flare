<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Skill;
use App\Http\Controllers\Controller;
use App\Game\Core\Requests\TrainSkillValidation;

class CharacterSkillController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function train(TrainSkillValidation $request) {
        $character = auth()->user()->character;

        // Update all skills.
        $character->skills->each(function($skill) {
            $skill->update([
                'currently_training' => false,
                'xp_twoards'         => 0.0,
            ]);
        });

        // Find the skill we want to train.
        $skill = $character->refresh()->skills->filter(function ($skill) use($request) {
            return $skill->id === (int) $request->skill_id;
        })->first();

        if (is_null($skill)) {
            return redirect()->back()->with('error', 'Invalid Input.');
        }

        // Beggin training
        $skill->update([
            'currently_training' => true,
            'xp_towards'         => $request->xp_percentage,
            'xp_max'             => rand(100, 1000),
        ]);

        return redirect()->back()->with('success', 'You are now training ' . $skill->name);
    }

    public function show(Skill $skill) {
        return view('game.core.character.skill', [
            'skill' => $skill
        ]);
    }
}
