<?php

namespace App\Game\Automation\Services;


use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Services\FightService;
use App\Flare\Traits\ClassBasedBonuses;
use App\Game\Automation\Events\AutomatedAttackMessage;
use App\Game\Automation\Events\AutomationAttackTimeOut;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Jobs\BattleAttackHandler;

class ProcessAttackAutomation {

    use ClassBasedBonuses;

    private $fightService;

    private $battleEventHandler;

    public function __construct(FightService $fightService, BattleEventHandler $battleEventHandler) {
        $this->fightService        = $fightService;
        $this->battleEventHandler = $battleEventHandler;
    }

    public function processFight(CharacterAutomation $automation, Character $character, string $attackType) {
        $monster = $automation->monster;

        $this->fightService->processFight($character, $monster, $attackType);

        $battleMessages = $this->fightService->getBattleMessages();

        if ($this->fightService->tookTooLong()) {
            $automation->delete();

            $battleMessages[] = [
                'message' => 'This took way to long! Check your gear child!',
                'class'   => 'enemy-action-fired',
            ];

            event(new AutomatedAttackMessage($character->user, $battleMessages));

            return 0;
        }

        if ($this->fightService->getCharacterHealth() <= 0) {
            $automation->delete();

            $battleMessages[] = [
                'message' => 'You have died during the fight! Death has come for you! The Automation has been stopped',
                'class'   => 'enemy-action-fired',
            ];

            event(new AutomatedAttackMessage($character->user, $battleMessages));

            $this->battleEventHandler->processDeadCharacter($character);

            return 0;
        }

        event(new AutomatedAttackMessage($character->user, $battleMessages));
        
        if ($this->fightService->getMonsterHealth() <= 0) {
            BattleAttackHandler::dispatch($character->refresh(), $automation->monster_id)->onQueue('default_long');
        }
        
        $time = 10;

        $time = $time - ($time * $this->findTimeReductions($character));

        if ($time <= 0) {
            $time = 1;
        }

        event(new AutomationAttackTimeOut($character->user, $time));
        
        return $time;
    }

    protected function findTimeReductions(Character $character) {
        $skill = $character->skills->filter(function($skill) {
            return ($skill->fight_time_out_mod > 0.0) && is_null($skill->baseSkill->game_class_id);
        })->first();


        if (is_null($skill)) {
            return 0;
        }

        $classBonus = $this->getThievesFightTimeout($character) + $this->getRangersFightTimeout($character);

        return $skill->fight_time_out_mod + $classBonus;
    }
}