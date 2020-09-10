<?php

namespace App\Game\Core\Controllers;

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

    public function train(TrainSkillValidation $request) {
        $character = auth()->user()->character;

        // Find the skill we want to train.
        $skill = $character->refresh()->skills->filter(function ($skill) use($request) {
            return $skill->id === (int) $request->skill_id;
        })->first();

        if (is_null($skill)) {
            return redirect()->back()->with('error', 'Invalid Input.');
        }

        // Update all skills.
        $character->skills->each(function($skill) {
            $skill->update([
                'currently_training' => false,
                'xp_twoards'         => 0.0,
            ]);
        });

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

    public function cancelTrain(Skill $skill) {
        $skill = auth()->user()->character->skills()->find($skill->id);

        if (is_null($skill)) {
            return redirect()->back()->with('success', 'Invalid input.');
        }

        $skill->update([
            'currently_training' => false,
            'xp_twoards'         => 0.0,
        ]);

        return redirect()->back()->with('success', 'You stopped training: ' . $skill->name);
    }
}
