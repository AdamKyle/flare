<?php

namespace App\Game\Battle\Services;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Monster;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Jobs\CelestialTimeOut;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Core\Events\UpdateCharacterCelestialTimeOut;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\MercenaryBonus;
use App\Game\Mercenaries\Values\MercenaryValue;
use Facades\App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Game\Battle\Events\UpdateCelestialFight;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Jobs\BattleAttackHandler;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Support\Facades\Cache;

class RankFightService {

    use ResponseBuilder, MercenaryBonus;

    private BattleEventHandler $battleEventHandler;

    private CharacterCacheData $characterCacheData;

    private ?MonsterPlayerFight $monsterPlayerFight;

    public function __construct(BattleEventHandler $battleEventHandler, CharacterCacheData $characterCacheData, MonsterPlayerFight $monsterPlayerFight) {
        $this->battleEventHandler = $battleEventHandler;
        $this->characterCacheData = $characterCacheData;
        $this->monsterPlayerFight = $monsterPlayerFight;
    }

    public function setupFight(Character $character, Monster $monster, int $rank): array {

        $this->characterCacheData->characterSheetCache($character, true);

        $monsterPlayerFight = $this->monsterPlayerFight->setUpRankFight($character, $monster->id, $rank);

        if (is_array($monsterPlayerFight)) {
            return $this->errorResult($monsterPlayerFight['message']);
        }

        $data     = $monsterPlayerFight->fightSetUp(true);
        $health   = $data['health'];

        $messages = $monsterPlayerFight->getBattleMessages();

        if ($health['character_health'] <= 0) {
            $health['character_health'] = 0;

            $messages[] = [
                'message' => 'The enemies ambush has slaughtered you!',
                'type'    => 'enemy-action',
            ];

            $this->battleEventHandler->processDeadCharacter($character);

            $this->characterCacheData->deleteCharacterSheet($character);

            Cache::delete('rank-fight-for-character-' . $character->id);

            return $this->successResult([
                'health'   => $health,
                'messages' => $messages,
                'is_dead'  => true,
            ]);
        }

        if ($health['monster_health'] <= 0) {
            $health['monster_health'] = 0;

            $messages[] = [
                'message' => 'Your ambush has slaughtered the enemy!',
                'type'    => 'enemy-action',
            ];

            $this->characterCacheData->deleteCharacterSheet($character);

            Cache::delete('rank-fight-for-character-' . $character->id);

            $this->handleRankFightMonsterDeath($character, $monsterPlayerFight->getMonster());

            return $this->successResult([
                'health'   => $health,
                'messages' => $messages,
                'is_dead'  => false,
            ]);
        }

        return $this->successResult([
            'health'   => $health,
            'messages' => $messages,
            'is_dead'  => true,
        ]);
    }

    public function fight(Character $character, string $attackType): array {
        $result = $this->monsterPlayerFight->setCharacter($character)->fightMonster(true, true, $attackType);

        if ($result) {

            $messages = $this->monsterPlayerFight->getBattleMessages();

            $characterHealth = $this->monsterPlayerFight->getCharacterHealth();

            $this->handleRankFightMonsterDeath($character, $this->monsterPlayerFight->getMonster());

            $this->characterCacheData->deleteCharacterSheet($character);

            Cache::delete('rank-fight-for-character-' . $character->id);

            return $this->successResult([
                'messages' => $messages,
                'health'   => [
                    'character_health' => $characterHealth,
                    'monster_health'   => 0,
                ],
                'is_dead'  => false,
            ]);
        }

        $characterHealth = $this->monsterPlayerFight->getCharacterHealth();
        $characterHealth = max($characterHealth, 0);
        $monsterHealth   = $this->monsterPlayerFight->getMonsterHealth();

        if ($characterHealth === 0) {
            $this->battleEventHandler->processDeadCharacter($character);

            $this->characterCacheData->deleteCharacterSheet($character);

            Cache::delete('rank-fight-for-character-' . $character->id);

            return $this->successResult([
                'health'   => [
                    'character_health' => 0,
                    'monster_health'   => $monsterHealth,
                ],
                'messages' => $this->monsterPlayerFight->getBattleMessages(),
                'is_dead'  => true,
            ]);
        }


        return $this->successResult([
            'logs'      => $this->monsterPlayerFight->getBattleMessages(),
            'health' => [
                'character_health' => $characterHealth,
                'monster_health'   => $this->monsterPlayerFight->getMonsterHealth(),
            ]
        ]);
    }

    public function revive(Character $character) {
        $character = $this->battleEventHandler->processRevive($character);

        $characterInCelestialFight = CharacterInCelestialFight::where('character_id', $character->id)->first();
        $celestialFight            = CelestialFight::find($characterInCelestialFight->celestial_fight_id);

        return $this->successResult([
            'fight' => [
                'character' =>[
                    'max_health'     => $characterInCelestialFight->character_max_health,
                    'current_health' => $characterInCelestialFight->character_current_health,
                ],
                'monster' => [
                    'max_health'     => $celestialFight->max_health,
                    'current_health' => $celestialFight->current_health,
                ],
            ],
        ]);
    }

    protected function handleRankFightMonsterDeath(Character $character, array $monster): void {

        BattleAttackHandler::dispatch($character->id, $monster['id'])->onQueue('default_long')->delay(now()->addSeconds(2));

        event(new AttackTimeOutEvent($character));
    }
}
