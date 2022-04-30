<?php

namespace App\Game\Skills\Controllers\Api;

use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\Skills\Jobs\ProcessAlchemy;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Skills\Requests\AlchemyValidation;
use App\Game\Skills\Services\AlchemyService;

class PassiveSkillsController extends Controller {

    private $characterPassiveSkills;

    public function __construct(CharacterPassiveSkills $characterPassiveSkills) {
        $this->characterPassiveSkills = $characterPassiveSkills;
    }

    public function getKingdomPassives(Character $character) {
        return response()->json([
            'kingdom_passives' => $this->characterPassiveSkills->getPassiveSkills($character),
        ]);
    }
}
