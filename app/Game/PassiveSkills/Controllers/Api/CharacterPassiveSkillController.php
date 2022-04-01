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

    public function __construct(PassiveSkillTrainingService $passiveSkillTrainingService) {
        $this->passiveSkillTrainingService = $passiveSkillTrainingService;
    }

    public function trainSkill(CharacterPassiveSkill $characterPassiveSkill, Character $character) {
        $this->passiveSkillTrainingService->trainSkill($characterPassiveSkill, $character);

        return response()->json([
            'message' => 'Started training ' . $characterPassiveSkill->passiveSkill->name,
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

        event(new UpdatePassiveTree($character->user, $characterPassiveSkills->getPassiveSkills($character)));

        return response()->json([
            'message' => 'Stopped training ' . $characterPassiveSkill->passiveSkill->name,
        ]);
    }
}
