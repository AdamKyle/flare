<?php

namespace App\Game\Reincarnate\Controllers\Api;

use App\Flare\Models\PassiveSkill;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Quests\Services\QuestHandlerService;
use App\Game\Reincarnate\Services\CharacterReincarnateService;
use App\Game\Skills\Values\SkillTypeValue;
use Cache;
use App\Flare\Models\Character;
use App\Flare\Models\Quest;
use App\Http\Controllers\Controller;

class ReincarnateController extends Controller {

    /**
     * @var CharacterReincarnateService $reincarnateService
     */
    private CharacterReincarnateService $reincarnateService;

    /**
     * @param CharacterReincarnateService $reincarnateService
     */
    public function __construct(CharacterReincarnateService $reincarnateService) {
        $this->reincarnateService = $reincarnateService;
    }

   public function reincarnate(Character $character) {
        $result = $this->reincarnateService->reincarnate($character);

        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
   }
}
