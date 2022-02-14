<?php

namespace App\Game\Exploration\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Game\Exploration\Events\ExplorationLogUpdate;

class PlunderHandler {

    private $fightHandler;

    public function __construct(FightHandler $fightHandler) {
        $this->fightHandler = $fightHandler;
    }

    public function plunder(Character $character, CharacterAutomation $characterAutomation) {
        event(new ExplorationLogUpdate($character->user, 'Oh, we just had to track down these creatures and rip them to shreds didn\'t we? One small peaceful walk turns into a blood bath. How fun ...', true));

        $fightAmount = rand(1, 6);

        while ($fightAmount != 0) {
            $this->fightHandler->fight($character, $characterAutomation, false);

            $fightAmount--;
        }

        event(new ExplorationLogUpdate($character->user, 'Good lord child! We barley survived that. or at least I barley survived that. Can we just have a peaceful walk in the forest without you trying to kill us? Is that too hard to ask? Christ!', true));
    }
}
