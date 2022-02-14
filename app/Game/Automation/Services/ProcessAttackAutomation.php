<?php

namespace App\Game\Automation\Services;

use App\Game\Exploration\Events\ExplorationLogUpdate;
use Cache;
use App\Flare\Builders\Character\ClassDetails\ClassBonuses;
use App\Flare\Models\Monster;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Location;
use App\Flare\Services\FightService;
use App\Game\Automation\Events\AutomatedAttackMessage;
use App\Game\Automation\Events\AutomationAttackTimeOut;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Jobs\BattleAttackHandler;

class ProcessAttackAutomation {

    private $fightService;

    private $battleEventHandler;

    private $classBonuses;

    public function __construct(FightService $fightService, BattleEventHandler $battleEventHandler, ClassBonuses $classBonuses) {
        $this->classBonuses        = $classBonuses;
        $this->fightService        = $fightService;
        $this->battleEventHandler  = $battleEventHandler;
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

        event(new ExplorationLogUpdate($character->user, 'Encountered a: ' . $monster->name));

        $this->fightService->processFight($character, $monster, $attackType);

        $battleMessages = $this->fightService->getBattleMessages();

        if ($this->fightService->tookTooLong()) {
            $automation->delete();

            $battleMessages[] = [
                'message' => 'This took way to long! Check your gear child!',
                'class'   => 'enemy-action-fired',
            ];

            event(new AutomatedAttackMessage($character->user, $battleMessages));

            event(new ExplorationLogUpdate($character->user, 'You and the enemy are just missing each other ...'));

            return 0;
        }

        if ($this->fightService->getCharacterHealth() <= 0) {
            $automation->delete();

            $battleMessages[] = [
                'message' => 'The Automation has been stopped! Revive to try again.',
                'class'   => 'enemy-action-fired',
            ];

            event(new AutomatedAttackMessage($character->user, $battleMessages));

            event(new ExplorationLogUpdate($character->user, 'Oh! Christ! You\'re dead.'));

            $this->battleEventHandler->processDeadCharacter($character);

            return 0;
        }



        if ($this->fightService->getMonsterHealth() <= 0) {
            event(new ExplorationLogUpdate($character->user, 'Your survived to fight another day, killing the fiend before you with such rage and vigor! Off to the next one!'));

            BattleAttackHandler::dispatch($character->id, $automation->monster_id, true)->onQueue('default_long');
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
}
