<?php

namespace App\Game\Automation\Services;

use App\Flare\Models\Monster;
use Cache;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Location;
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
        $location = $this->isCharacterAtSpecialLocation($character);

        if (!is_null($location)) {
            $monsters = Cache::get('monsters')[$location->name];

            $monsterIndex = array_search($automation->monster_id, array_column($monsters, 'id'));

            if ($monsterIndex !== false) {
                // Create a new monster object, but don't save it.
                // This allows attacks to work as they require a monster object.
                $monster = new Monster($monsters[$monsterIndex]);
            } else {
                $monster = $automation->monster;
            }
        } else {
            $monster = $automation->monster;
        }

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
                'message' => 'The Automation has been stopped! Revive to try again.',
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

        event(new AutomationAttackTimeOut($character->user, 10));

        return 10;
    }

    protected function isCharacterAtSpecialLocation(Character $character): ?Location {
        $location = Location::where('x', $character->x_position)
                            ->where('y', $character->y_position)
                            ->where('game_map_id', $character->map->game_map_id)
                            ->first();

        if (!is_null($location)) {
            if (!is_null($location->enemy_strength_type)) {
                return $location;
            }
        }

        return null;
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