<?php

namespace App\Flare\ServerFight;

use App\Flare\ServerFight\Fight\Ambush;
use App\Flare\ServerFight\Fight\Attack;
use App\Game\Battle\Handlers\BattleEventHandler;
use Cache;
use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\ServerFight\Fight\Voidance;
use App\Flare\ServerFight\Monster\BuildMonster;
use App\Game\Core\Traits\ResponseBuilder;
use App\Flare\Models\Character;
use App\Flare\Services\BuildMonsterCacheService;

class MonsterPlayerFight {

    use ResponseBuilder;

    private array $monster;

    private array $battleMessages;

    private string $attackType;

    private bool $tookTooLong;

    private Character $character;

    private BuildMonster $buildMonster;

    private CharacterCacheData $characterCacheData;

    private Voidance $voidance;

    private Ambush $ambush;

    private Attack $attack;

    public function __construct(BuildMonster $buildMonster, CharacterCacheData $characterCacheData, Voidance $voidance, Ambush $ambush, Attack $attack) {
        $this->buildMonster       = $buildMonster;
        $this->characterCacheData = $characterCacheData;
        $this->voidance           = $voidance;
        $this->ambush             = $ambush;
        $this->attack             = $attack;
        $this->battleMessages     = [];
        $this->tookTooLong        = false;
    }

    public function setUpFight(Character $character, array $params) {
        $this->character = $character;
        $this->monster   = $this->fetchMonster($character->map->gameMap->name, $params['selected_monster_id']);
        $this->attackType = $params['attack_type'];

        if (empty($this->monster)) {
            return $this->errorResult('No monster was found.');
        }

        return $this;
    }

    public function getTookTooLong(): bool {
        return $this->tookTooLong;
    }

    public function deleteCharacterCache(Character $character) {
        $this->characterCacheData->deleteCharacterSheet($character);
    }

    public function getBattleMessages() {
        return $this->battleMessages;
    }

    public function resetBattleMessages() {
        $this->battleMessages = [];

        $this->voidance->clearMessages();;
        $this->ambush->clearMessages();
        $this->attack->resetBattleMessages();
    }

    public function getEnemyName() {
        return $this->monster['name'];
    }

    public function fightMonster(): bool {

        $this->characterCacheData->deleteCharacterSheet($this->character);

        $characterStatReductionAffixes = $this->characterCacheData->getCachedCharacterData($this->character, 'stat_affixes');
        $skillReduction                = $this->characterCacheData->getCachedCharacterData($this->character, 'skill_reduction');
        $resistanceReduction           = $this->characterCacheData->getCachedCharacterData($this->character, 'resistance_reduction');

        $monster = $this->buildMonster->buildMonster($this->monster, $characterStatReductionAffixes, $skillReduction, $resistanceReduction);

        $this->voidance->void($this->character, $this->characterCacheData, $monster);

        $this->mergeMessages($this->voidance->getMessages());

        $this->mergeMessages($this->buildMonster->getMessages());

        $isPlayerVoided = $this->voidance->isPlayerVoided();

        $ambush = $this->ambush->handleAmbush($this->character, $monster, $isPlayerVoided);

        $this->mergeMessages($ambush->getMessages());

        $health = $ambush->getHealthObject();

        if ($health['character_health'] <= 0) {
            $this->battleMessages[] = [
                'message' => 'The enemies ambush has slaughtered you!',
                'type'    => 'enemy-action',
            ];

            return false;
        }

        if ($health['monster_health'] <= 0) {
            $this->battleMessages[] = [
                'message' => 'Your ambush has slaughtered the enemy!',
                'type'    => 'enemy-action',
            ];

            return true;
        }

        $this->attack->setHealth($ambush->getHealthObject())
                     ->setIsCharacterVoided($isPlayerVoided)
                     ->attack($this->character, $monster, $this->attackType, 'character');

        $this->mergeMessages($this->attack->getMessages());

        $this->attack->resetBattleMessages();

        if ($this->attack->getCharacterHealth() <= 0) {
            return false;
        }

        if ($this->attack->getMonsterHealth() <= 0) {
            return true;
        }

        $this->tookTooLong = $this->attack->tookTooLong();

        return false;
    }

    protected function fetchMonster(string $mapName, int $monsterId): array {
        if (!Cache::has('monsters')) {
            resolve(BuildMonsterCacheService::class)->buildCache();
        }

        $monsters = Cache::get('monsters')[$mapName];

        foreach ($monsters as $monster) {
            if ($monster['id'] === $monsterId) {
                return $monster;
            }
        }

        return [];
    }

    protected function mergeMessages(array $messages) {
        $this->battleMessages = [...$this->battleMessages, ...$messages];
    }
}
