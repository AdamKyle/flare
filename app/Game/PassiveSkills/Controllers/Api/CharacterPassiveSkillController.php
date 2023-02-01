<?php

namespace App\Game\PassiveSkills\Controllers\Api;

use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\PassiveSkills\Events\UpdatePassiveTree;
use App\Game\PassiveSkills\Services\PassiveSkillTrainingService;
use App\Http\Controllers\Controller;

class CharacterPassiveSkillController extends Controller {

    private $passiveSkillTrainingService;

    private $characterPassiveSkills;

    public function __construct(PassiveSkillTrainingService $passiveSkillTrainingService, CharacterPassiveSkills $characterPassiveSkills) {
        $this->passiveSkillTrainingService = $passiveSkillTrainingService;
        $this->characterPassiveSkills      = $characterPassiveSkills;
    }

    public function getKingdomPassives(Character $character) {
        return response()->json([
            'kingdom_passives' => $this->characterPassiveSkills->getPassiveSkills($character),
            'passive_training' => $this->characterPassiveSkills->getPassiveInTraining($character),
        ]);
    }

    public function trainSkill(CharacterPassiveSkill $characterPassiveSkill, Character $character) {

        if ($characterPassiveSkill->character_id !== $character->id) {
            return response()->json(['message' => 'You do not own that.'], 422);
        }

        $passivesRunning = CharacterPassiveSkill::where('character_id', $character->id)
                                                ->whereNotNull('started_at')
                                                ->count();

        if ($passivesRunning > 0) {
            return response()->json(['message' => 'Only one passive allowed to train at a time.'], 422);
        }

        $this->passiveSkillTrainingService->trainSkill($characterPassiveSkill, $character);

        $character = $character->refresh();

        return response()->json([
            'message'          => 'Started training ' . $characterPassiveSkill->passiveSkill->name,
            'kingdom_passives' => $this->characterPassiveSkills->getPassiveSkills($character),
            'passive_training' => $this->characterPassiveSkills->getPassiveInTraining($character),
        ]);
    }

    public function stopTraining(CharacterPassiveSkill $characterPassiveSkill, Character $character, CharacterPassiveSkills $characterPassiveSkills) {
        $characterPassiveSkill->update([
            'started_at' => null,
            'completed_at' => null
        ]);

        $characterPassiveSkill = $characterPassiveSkill->refresh();

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        return response()->json([
            'message'          => 'Stopped training ' . $characterPassiveSkill->passiveSkill->name,
            'kingdom_passives' => $this->characterPassiveSkills->getPassiveSkills($character),
        ]);
    }
}
