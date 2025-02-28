<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Game\Skills\Requests\TrainSkillValidation;
use App\Game\Skills\Services\SkillService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SkillsController extends Controller
{

    public function __construct(private SkillService $skillService) {}

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function fetchSkills(Character $character): JsonResponse
    {
        $trainableSkillIds = GameSkill::where('can_train', true)->pluck('id')->toArray();
        $craftingSkills = GameSkill::where('can_train', false)->pluck('id')->toArray();

        return response()->json([
            'training_skills' => $this->skillService->getSkills($character, $trainableSkillIds),
            'crafting_skills' => $this->skillService->getSkills($character, $craftingSkills),
        ]);
    }

    /**
     * @param Character $character
     * @param Skill $skill
     * @return JsonResponse
     */
    public function skillInformation(Character $character, Skill $skill): JsonResponse
    {

        if ($character->id !== $skill->character_id) {
            return response()->json([
                'message' => 'No. Not allowed to do that.',
            ], 422);
        }

        return response()->json($this->skillService->getSkill($skill));
    }

    /**
     * @param TrainSkillValidation $request
     * @param Character $character
     * @return JsonResponse
     */
    public function train(TrainSkillValidation $request, Character $character): JsonResponse
    {
        $result = $this->skillService->trainSkill($character, $request->skill_id, $request->xp_percentage);

        return response()->json([
            'message' => $result['message'],
            'skills' => [
                'training_skills' => $this->skillService->getSkills($character->refresh(), GameSkill::where('can_train', true)->pluck('id')->toArray()),
            ],
        ], $result['status']);
    }

    /**
     * @param Character $character
     * @param Skill $skill
     * @return JsonResponse
     */
    public function cancelTrain(Character $character, Skill $skill): JsonResponse
    {
        if (is_null($character->skills()->find($skill->id))) {
            return response()->json(['message' => 'Nope. You cannot do that.'], 422);
        }

        $skill->update([
            'currently_training' => false,
            'xp_towards' => 0.0,
        ]);

        return response()->json([
            'message' => 'You stopped training: ' . $skill->name,
            'skills' => [
                'training_skills' => $this->skillService->getSkills($character->refresh(), GameSkill::where('can_train', true)->pluck('id')->toArray()),
            ],
        ]);
    }
}
