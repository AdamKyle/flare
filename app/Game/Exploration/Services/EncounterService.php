<?php

namespace App\Game\Exploration\Services;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Exploration\Events\ExplorationStatus;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\Exploration\Events\UpdateAutomationsList;
use App\Game\Exploration\Handlers\ExplorationHandler;
use App\Game\Exploration\Handlers\FightHandler;
use App\Game\Exploration\Handlers\PlunderHandler;
use App\Game\Exploration\Handlers\RewardHandler;

class EncounterService {

    private $explorationHandler;
    private $fightHandler;
    private $plunderhandler;
    private $rewardHandler;

    public function __construct(ExplorationHandler $explorationHandler, FightHandler $fightHandler, PlunderHandler $plunderHandler, RewardHandler $rewardHandler) {

        $this->explorationHandler = $explorationHandler;
        $this->fightHandler       = $fightHandler;
        $this->plunderhandler     = $plunderHandler;
        $this->rewardHandler      = $rewardHandler;
    }

    public function processEncounter(Character $character, CharacterAutomation $characterAutomation) {
        try {
            event(new ExplorationLogUpdate($character->user, 'Off down the road you go, what wonders will you come across today? Lets go child, I don\'t have all day!'));

            $this->explorationHandler->explore($character, $characterAutomation);

            $this->fightHandler->fight($character, $characterAutomation);

            event(new ExplorationLogUpdate($character->user, 'You set out on your journeys to track down the lair or camp in which these horrid creatures call home!'));

            $this->plunderhandler->plunder($character, $characterAutomation);

            event(new ExplorationLogUpdate($character->user, 'Covered in blood, wreaking of death and justice for the land, you emerge victorious at the local INN. A busty large red headed Bar Maid comes up to you. With her cleavage on the table, she leans down and smiles with her rotten and missing teeth, "What can I get ya doll?" you shudder on the inside.', true));

            $this->rewardHandler->processRewardsForEncounter($character);

            return true;
        } catch (\Exception $e) {
            $character = $character->refresh();

            event(new ExplorationTimeOut($character->user, 0));
            event(new ExplorationStatus($character->user, false));
            event(new UpdateTopBarEvent($character));
            event(new UpdateAutomationsList($character->user, $character->currentAutomations));

            return false;
        }
    }

}
