<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Skills\Requests\TrainSkillValidation;
use App\Game\Skills\Services\SkillService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SkillsController extends Controller
{
    /**
     * @param SkillService $skillService
     * @param AutomationRestrictionService $automationRestrictionService
     * @param UpdateCharacterSkillsService $updateCharacterSkillsService
     */
    public function __construct(
        private SkillService $skillService,
        private readonly AutomationRestrictionService $automationRestrictionService,
        private readonly UpdateCharacterSkillsService $updateCharacterSkillsService,
    ) {}

    /**
     * Fetch the Character's Training and Crafting Skills.
     *
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
     * Fetch detailed Skill information with the current training-management authority.
     *
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

        $skillData = $this->skillService->getSkill($skill);

        $restriction = $this->automationRestrictionService->blockedContext(
            $character,
            AutomationRestrictionService::PLAYER_SKILLS
        );

        return response()->json([
            ...$skillData,
            'can_manage_training' => is_null($restriction),
            'training_restriction_message' => $restriction['message'] ?? null,
        ]);
    }

    /**
     * Start training the requested Character Skill and return the refreshed Training Skills.
     *
     * @param TrainSkillValidation $request
     * @param Character $character
     * @return JsonResponse
     */
    public function train(TrainSkillValidation $request, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->skillService->trainSkill($character, $request->skill_id, $request->xp_percentage);
        $character = $character->refresh();

        $this->updateCharacterSkillsService->updateCharacterSkills($character);

        return response()->json([
            'message' => $result['message'],
            'skills' => [
                'training_skills' => $this->skillService->getSkills($character, GameSkill::where('can_train', true)->pluck('id')->toArray()),
            ],
        ], $result['status']);
    }

    /**
     * Cancel training the requested Character Skill and return the refreshed Training Skills.
     *
     * @param Character $character
     * @param Skill $skill
     * @return JsonResponse
     */
    public function cancelTrain(Character $character, Skill $skill): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        if (is_null($character->skills()->find($skill->id))) {
            return response()->json(['message' => 'Nope. You cannot do that.'], 422);
        }

        $skill->update([
            'currently_training' => false,
            'xp_towards' => 0.0,
        ]);
        $character = $character->refresh();

        $this->updateCharacterSkillsService->updateCharacterSkills($character);

        return response()->json([
            'message' => 'You stopped training: '.$skill->name,
            'skills' => [
                'training_skills' => $this->skillService->getSkills($character, GameSkill::where('can_train', true)->pluck('id')->toArray()),
            ],
        ]);
    }

    /**
     * Build the automation restriction response for Skill-management actions when blocked.
     *
     * @param Character $character
     * @return ?JsonResponse
     */
    private function automationRestrictionJsonResponse(Character $character): ?JsonResponse
    {
        $restriction = $this->automationRestrictionService->blockedContext($character, AutomationRestrictionService::PLAYER_SKILLS);

        if (is_null($restriction)) {
            return null;
        }

        return response()->json(['message' => $restriction['message']], 422);
    }
}
