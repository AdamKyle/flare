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

        $cache = Cache::delete('monster-fight-' . $character->id);

        if ($cache) {
            return $cache;
        }

        $data = $this->monsterPlayerFight->setUpFight($character, $params)->fightSetUp();  
        
        Cache::put('monster-fight-' . $character->id, $data, 15);

        $data['messages'] = $this->monsterPlayerFight->getBattleMessages();

        return $this->successResult($data);
    }

    public function fightMonster(Character $character, string $attackType): array {
        $cache = Cache::get('monster-fight-' . $character->id);

        if (is_null($cache)) {
            return $this->errorResult('There seems to be no monster here. Hmmm, maybe you waited too long and it fled. 
            Try attackig it again! (you have 15 minutes from selecting and clicking fight to kill it. Each attack resets this timer)');
        }

        $this->monsterPlayerFight->fightMonster();

        if ($this->monsterPlayerFight->getCharacterHealth() <= 0) {
            $this->battleEventHandler->processDeadCharacter($character);
        }

        $cache['health'] = [
            'current_character_health' => $this->monsterPlayerFight->getCharacterHealth(),
            'current_monster_health'   => $this->monsterPlayerFight->getMonsterHealth(),
        ];

        if ($this->monsterPlayerFight->getMonsterHealth() >= 0) {
            Cache::put('monster-fight-' . $character->id, $cache, 15);
        } else {
            Cache::delete('monster-fight-' . $character->id);
            
            BattleAttackHandler::dispatch($character->id, $this->monsterPlayerFight->getMonster()['id'])->onQueue('default_long')->delay(now()->addSeconds(2));
        }
        
        $cache['messages'] = $this->monsterPlayerFight->getBattleMessages();

        return $this->successResult($cache);
    }
}