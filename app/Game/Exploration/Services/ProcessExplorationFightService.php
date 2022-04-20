<?php

namespace App\Game\Exploration\Services;

use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Exploration\Events\ExplorationStatus;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\Exploration\Events\UpdateAutomationsList;
use Cache;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Flare\Models\Monster;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Location;
use App\Flare\Services\FightService;
use App\Game\Exploration\Events\ExplorationAttackMessage;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Jobs\BattleAttackHandler;
use Matrix\Exception;

class ProcessExplorationFightService {

    private $fightService;

    private $battleEventHandler;

    public function __construct(FightService $fightService, BattleEventHandler $battleEventHandler) {
        $this->fightService        = $fightService;
        $this->battleEventHandler  = $battleEventHandler;
    }

    public function processFight(CharacterAutomation $automation, Character $character) {
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

        $this->fightService->processFight($character, $monster, $automation->attack_type);

        $battleMessages = $this->fightService->getBattleMessages();

        if ($this->fightService->tookTooLong()) {
            $automation->delete();

            $battleMessages[] = [
                'message' => 'This took way to long! Check your gear, child!',
                'class'   => 'enemy-action-fired',
            ];

            $character = $character->refresh();

            event(new ExplorationAttackMessage($character->user, $battleMessages));

            event(new ExplorationLogUpdate($character->user, 'You and the enemy are just missing each other...'));

            throw new Exception('battle took too long.');
        }

        if ($this->fightService->getCharacterHealth() <= 0) {
            $automation->delete();

            $battleMessages[] = [
                'message' => 'The exploration has stopped! Revive to try again.',
                'class'   => 'enemy-action-fired',
            ];

            $character = $character->refresh();

            event(new ExplorationAttackMessage($character->user, $battleMessages));

            event(new ExplorationLogUpdate($character->user, 'Oh! Christ! You\'re dead.', true));

            $this->battleEventHandler->processDeadCharacter($character);

            throw new Exception('Character is dead');
        }


        if ($this->fightService->getMonsterHealth() <= 0) {
            event(new ExplorationLogUpdate($character->user, 'Your survived to fight another day, killing the fiend before you with such rage and vigor! Off to the next one!'));

            BattleAttackHandler::dispatch($character->id, $automation->monster_id, true)->onQueue('default_long');
        }
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
