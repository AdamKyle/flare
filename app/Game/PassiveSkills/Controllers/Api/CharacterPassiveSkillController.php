<?php

namespace App\Game\PassiveSkills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
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

    public function stopTrainingSkill(CharacterPassiveSkill $characterPassiveSkill, Character $character) {

    }
}
