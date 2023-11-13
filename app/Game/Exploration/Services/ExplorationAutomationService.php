<?php

namespace App\Game\Exploration\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Flare\Values\AutomationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\Exploration\Jobs\Exploration;
use App\Game\Maps\Events\UpdateDuelAtPosition;

class ExplorationAutomationService {

    private MonsterPlayerFight $monsterPlayerFight;

    private BattleEventHandler $battleEventHandler;

    public function __construct(MonsterPlayerFight $monsterPlayerFight, BattleEventHandler $battleEventHandler) {
        $this->monsterPlayerFight = $monsterPlayerFight;
        $this->battleEventHandler = $battleEventHandler;
    }

    /**
     * @param Character $character
     * @param array $params
     */
    public function beginAutomation(Character $character, array $params) {

        $automation = CharacterAutomation::create([
            'character_id'                   => $character->id,
            'monster_id'                     => $params['selected_monster_id'],
            'type'                           => AutomationType::EXPLORING,
            'started_at'                     => now(),
            'completed_at'                   => now()->addHours($params['auto_attack_length']),
            'move_down_monster_list_every'   => $params['move_down_the_list_every'],
            'previous_level'                 => $character->level,
            'current_level'                  => $character->level,
            'attack_type'                    => $params['attack_type'],
        ]);

        event(new UpdateCharacterStatus($character));

        event(new ExplorationLogUpdate($character->user->id, 'The exploration will begin in 5 minutes. Every 5 minutes you will encounter the enemy up to a maximum of 8 times in a single "encounter"'));

        event(new ExplorationTimeOut($character->user, now()->diffInSeconds($automation->completed_at)));

        event(new UpdateDuelAtPosition($character->refresh()->user));

        $this->startAutomation($character, $automation->id, $params['attack_type']);
    }

    protected function startAutomation(Character $character, int $automationId, string $attackType) {
        Exploration::dispatch($character, $automationId, $attackType)->delay(now()->addMinutes(5))->onQueue('default_long');
    }
}
