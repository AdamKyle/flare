<?php

namespace App\Game\PassiveSkills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\PassiveSkills\Services\PassiveSkillTrainingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CharacterPassiveSkillController extends Controller
{
    public function __construct(
        private readonly PassiveSkillTrainingService $passiveSkillTrainingService,
        private readonly CharacterPassiveSkills $characterPassiveSkills,
        private readonly AutomationRestrictionService $automationRestrictionService,
    ) {}

    public function getKingdomPassives(Character $character)
    {
        return response()->json([
            'kingdom_passives' => $this->characterPassiveSkills->getPassiveSkills($character),
            'passive_training' => $this->characterPassiveSkills->getPassiveInTraining($character),
        ]);
    }

    public function trainSkill(CharacterPassiveSkill $characterPassiveSkill, Character $character)
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        if ($characterPassiveSkill->character_id !== $character->id) {
            return response()->json(['message' => 'You do not own that.'], 422);
        }

        if ($characterPassiveSkill->current_level >= $characterPassiveSkill->passiveSkill->max_level) {
            $this->passiveSkillTrainingService->trainSkill($characterPassiveSkill, $character);

            return response()->json(['message' => 'This passive skill is already maxed and cannot be trained.'], 422);
        }

        $passivesRunning = CharacterPassiveSkill::where('character_id', $character->id)
            ->whereNotNull('started_at')
            ->count();

        if ($passivesRunning > 0) {
            return response()->json(['message' => 'Only one passive allowed to train at a time.'], 422);
        }

        if (! $this->passiveSkillTrainingService->trainSkill($characterPassiveSkill, $character)) {
            return response()->json(['message' => 'This passive skill is already maxed and cannot be trained.'], 422);
        }

        $character = $character->refresh();

        return response()->json([
            'message' => 'Started training '.$characterPassiveSkill->passiveSkill->name,
            'kingdom_passives' => $this->characterPassiveSkills->getPassiveSkills($character),
            'passive_training' => $this->characterPassiveSkills->getPassiveInTraining($character),
        ]);
    }

    public function stopTraining(CharacterPassiveSkill $characterPassiveSkill, Character $character, CharacterPassiveSkills $characterPassiveSkills)
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        if ($characterPassiveSkill->character_id !== $character->id) {
            return response()->json(['message' => 'You do not own that.'], 422);
        }

        $characterPassiveSkill->update([
            'started_at' => null,
            'completed_at' => null,
        ]);

        $characterPassiveSkill = $characterPassiveSkill->refresh();

        $character = $character->refresh();

        event(new UpdateCharacterBaseDetailsEvent($character));

        return response()->json([
            'message' => 'Stopped training '.$characterPassiveSkill->passiveSkill->name,
            'kingdom_passives' => $this->characterPassiveSkills->getPassiveSkills($character),
        ]);
    }

    private function automationRestrictionJsonResponse(Character $character): ?JsonResponse
    {
        $restriction = $this->automationRestrictionService->blockedContext($character, AutomationRestrictionService::PLAYER_SKILLS);

        if (is_null($restriction)) {
            return null;
        }

        return response()->json(['message' => $restriction['message']], 422);
    }
}
