<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Skill;
use App\Game\Skills\Services\SkillService;
use App\Http\Controllers\Controller;
use App\Game\Skills\Requests\TrainSkillValidation;

class SkillsController extends Controller {

    public function train(TrainSkillValidation $request, Character $character, SkillService $skillService) {
        $result = $skillService->trainSkill($character, $request->skill_id, $request->xp_percentage);

        return response()->json([
            'message' => $result['message']
        ], $result['status']);
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
