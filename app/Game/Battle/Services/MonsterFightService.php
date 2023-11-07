<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\Character;
use Illuminate\Support\Facades\Cache;
use App\Game\Core\Traits\ResponseBuilder;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Game\Battle\Jobs\BattleAttackHandler;
use App\Game\Battle\Handlers\BattleEventHandler;

class MonsterFightService {

    use ResponseBuilder;

    private MonsterPlayerFight $monsterPlayerFight;
    private BattleEventHandler $battleEventHandler;

    public function __construct(MonsterPlayerFight $monsterPlayerFight, BattleEventHandler $battleEventHandler,) {
        $this->monsterPlayerFight = $monsterPlayerFight;
        $this->battleEventHandler = $battleEventHandler;
    }

    public function setupMonster(Character $character, array $params): array {

        Cache::delete('monster-fight-' . $character->id);

        $data = $this->monsterPlayerFight->setUpFight($character, $params)->fightSetUp();

        if ($data['health']['current_character_health'] <= 0) {
            $this->battleEventHandler->processDeadCharacter($character);
        } else {
            Cache::put('monster-fight-' . $character->id, $data, 900);
        }

        return $this->successResult($data);
    }

    public function fightMonster(Character $character, string $attackType): array {
        $cache = Cache::get('monster-fight-' . $character->id);

        if (is_null($cache)) {
            return $this->errorResult('The monster seems to have fled. Click attack again to start a new battle. You have 15 minutes from clicking attack to attack the creature.');
        }

        $this->monsterPlayerFight->setCharacter($character);

        $this->monsterPlayerFight->fightMonster(true, false, $attackType);

        if ($this->monsterPlayerFight->getCharacterHealth() <= 0) {
            $this->battleEventHandler->processDeadCharacter($character);
        }

        $monsterHealth   = $this->monsterPlayerFight->getMonsterHealth();
        $characterHealth = $this->monsterPlayerFight->getCharacterHealth();

        $cache['health']['current_character_health'] = $characterHealth;
        $cache['health']['current_monster_health']   = $monsterHealth;
        $cache['health']['current_character_health']         = $characterHealth;
        $cache['health']['current_monster_health']           = $monsterHealth;

        if ($monsterHealth >= 0) {
            Cache::put('monster-fight-' . $character->id, $cache, 900);
        } else {
            Cache::delete('monster-fight-' . $character->id);

            BattleAttackHandler::dispatch($character->id, $this->monsterPlayerFight->getMonster()['id'])->onQueue('default_long')->delay(now()->addSeconds(2));
        }

        $cache['messages'] = $this->monsterPlayerFight->getBattleMessages();

        return $this->successResult($cache);
    }
}
