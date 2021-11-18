<?php

namespace App\Game\Automation\Services;


use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Services\FightService;

class ProcessAttackAutomation {

    private $fightService;

    public function __construct(FightService $fightService) {
        $this->fightService = $fightService;
    }

    public function processFight(CharacterAutomation $automation, Character $character, string $attackType) {
        $monster = $automation->monster;

        $this->fightService->processFight($character, $monster, $attackType);

        $battleMessages = $this->fightService->getBattleMessages();

        if ($this->fightService->tookTooLong()) {
            $automation->delete();

            return;
        }

        if ($this->fightService->getCharacterHealth() <= 0) {
            $automation->delete();

            return;
        }

        dd($battleMessages);
    }
}