<?php

namespace App\Flare\ServerFight;

use App\Flare\ServerFight\Fight\Ambush;
use App\Flare\ServerFight\Fight\Attack;
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

    public function fightMonster() {
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
