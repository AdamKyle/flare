<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Skill;
use App\Http\Controllers\Controller;
use App\Game\Skills\Requests\TrainSkillValidation;

class SkillsController extends Controller {

    public function train(TrainSkillValidation $request, Character $character) {
        // Find the skill we want to train.
        $skill = $character->skills->filter(function ($skill) use($request) {
            return $skill->id === (int) $request->skill_id;
        })->first();

        if (is_null($skill)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
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

        event(new UpdateTopBarEvent($character));

        return response()->json(['message' => 'You are now training ' . $skill->name], 200);
    }

    public function cancelTrain(Character $character, Skill $skill) {
        if (is_null($character->skills()->find($skill->id))) {
            return response()->json(['message' => 'Nope. You cannot do that.'], 422);
        }

        $skill->update([
            'currently_training' => false,
            'xp_towards'         => 0.0,
        ]);

        event(new UpdateTopBarEvent($character));

        return response()->json(['message' => 'You stopped training: ' . $skill->name], 200);
    }
}
