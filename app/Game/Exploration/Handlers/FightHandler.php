<?php

namespace App\Game\Exploration\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Exploration\Services\ProcessExplorationFightService;

class FightHandler {

    private $processExplorationFightService;

    public function __construct(ProcessExplorationFightService $processExplorationFightService) {
        $this->processExplorationFightService = $processExplorationFightService;
    }

    public function fight(Character $character, CharacterAutomation $characterAutomation, bool $triggerMessage = true) {
        if ($triggerMessage) {
            event(new ExplorationLogUpdate($character->user, 'Christ, child! Behind you, you really pissed these creatures off didn\'t ya? Couldn\'t leave well enough alone. No, we had to be the hero!', true));
        }

        $this->processExplorationFightService->processFight($characterAutomation, $character);
    }
}
