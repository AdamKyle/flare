<?php

namespace App\Flare\ServerFight;

use App\Flare\ServerFight\Monster\ServerMonster;
use Illuminate\Support\Facades\Cache;
use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\Ambush;
use App\Flare\ServerFight\Fight\Attack;
use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\GameMap;
use App\Flare\ServerFight\Fight\Voidance;
use App\Flare\ServerFight\Monster\BuildMonster;
use App\Flare\Services\BuildMonsterCacheService;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Core\Traits\ResponseBuilder;

class MonsterPlayerFight {

    use ResponseBuilder;

    /**
     * @var array $monster
     */
    private array $monster;

    /**
     * @var array $battleMessages
     */
    private array $battleMessages;

    /**
     * @var string $attackType
     */
    private string $attackType;

    /**
     * @var int $rank
     */
    private int $rank = 0;

    /**
     * @var bool $tookTooLong
     */
    private bool $tookTooLong;

    /**
     * @var Character $character
     */
    private Character $character;

    /**
     * @var BuildMonster $buildMonster
     */
    private BuildMonster $buildMonster;

    /**
     * @var CharacterCacheData $characterCacheData
     */
    private CharacterCacheData $characterCacheData;

    /**
     * @var Voidance $voidance
     */
    private Voidance $voidance;

    /**
     * @var Ambush $ambush
     */
    private Ambush $ambush;

    /**
     * @var Attack $attack
     */
    private Attack $attack;

    /**
     * @param BuildMonster $buildMonster
     * @param CharacterCacheData $characterCacheData
     * @param Voidance $voidance
     * @param Ambush $ambush
     * @param Attack $attack
     */
    public function __construct(BuildMonster $buildMonster,
                                CharacterCacheData $characterCacheData,
                                Voidance $voidance,
                                Ambush $ambush,
                                Attack $attack
    ) {
        $this->buildMonster       = $buildMonster;
        $this->characterCacheData = $characterCacheData;
        $this->voidance           = $voidance;
        $this->ambush             = $ambush;
        $this->attack             = $attack;
        $this->battleMessages     = [];
        $this->tookTooLong        = false;
    }

    /**
     * Set the character.
     *
     * - Useful for rank fights where the setup is already done and in the cache.
     *
     * @param Character $character
     * @return MonsterPlayerFight
     */
    public function setCharacter(Character $character): MonsterPlayerFight {
        $this->character = $character;

        return $this;
    }

    /**
     * Set up the fight.
     *
     * - Can return an error if the monster is not found.
     *
     * @param Character $character
     * @param array $params
     * @return array|$this
     */
    public function setUpFight(Character $character, array $params): MonsterPlayerFight|array {
        $this->character = $character;
        $this->monster   = $this->fetchMonster($character->map->gameMap->name, $params['selected_monster_id']);
        $this->attackType = $params['attack_type'];

        if (empty($this->monster)) {
            return $this->errorResult('No monster was found.');
        }

        return $this;
    }

    /**
     * Set up the rank fight.
     *
     * @param Character $character
     * @param int $monsterId
     * @param int $rank
     * @return array|$this
     */
    public function setUpRankFight(Character $character, int $monsterId, int $rank): MonsterPlayerFight|array {

        $rankMonster = $this->fetchRankMonster($monsterId, $rank);

        if (is_null($rankMonster)) {
            return $this->errorResult('No monster was found.');
        }

        $this->character = $character;
        $this->monster   = $rankMonster;

        return $this;
    }

    /**
     * Setup  the raid fight
     *
     * @param Character $character
     * @param array $raidMonster
     * @return MonsterPlayerFight
     */
    public function setUpRaidFight(Character $character, array $raidMonster, string $attackType): MonsterPlayerFight {

        $this->monster    = $raidMonster;
        $this->character  = $character;
        $this->attackType = $attackType;

        return $this;
    }

    /**
     * Did the fight take too long?
     *
     * @return bool
     */
    public function getTookTooLong(): bool {
        return $this->tookTooLong;
    }

    /**
     * Delete the cache data for the character
     *
     * @param Character $character
     * @return void
     */
    public function deleteCharacterCache(Character $character): void {
        $this->characterCacheData->deleteCharacterSheet($character);
    }

    /**
     * Get the battle messages.
     *
     * @return array
     */
    public function getBattleMessages(): array {
        $messages = $this->battleMessages;

        $this->removeDuplicateMessages($messages);

        return $messages;
    }

    /**
     * Reset all battle messages.
     *
     * @return void
     */
    public function resetBattleMessages(): void {
        $this->battleMessages = [];

        $this->voidance->clearMessages();;
        $this->ambush->clearMessages();
        $this->attack->resetBattleMessages();
    }

    /**
     * Get the enemy name.
     *
     * @return string
     */
    public function getEnemyName(): string {
        return $this->monster['name'];
    }

    /**
     * get the monster.
     *
     * @return array
     */
    public function getMonster(): array {
        return $this->monster;
    }

    /**
     * Get the character health.
     *
     * @return int
     */
    public function getCharacterHealth(): int {
        return $this->attack->getCharacterHealth();
    }

    /**
     * Get the monster health.
     *
     * @return int
     */
    public function getMonsterHealth(): int {
        return $this->attack->getMonsterHealth();
    }

    /**
     * @return int
     */
    public function getRank(): int {
        return $this->rank;
    }

    /**
     * Base Fight Setup.
     *
     * @param int $rank
     * @param bool $isRankFight
     * @return array
     */
    public function fightSetUp(int $rank = 0, bool $isRankFight = false): array {
        $characterStatReductionAffixes = $this->characterCacheData->getCachedCharacterData($this->character, 'stat_affixes');
        $skillReduction                = $this->characterCacheData->getCachedCharacterData($this->character, 'skill_reduction');
        $resistanceReduction           = $this->characterCacheData->getCachedCharacterData($this->character, 'resistance_reduction');

        $monster       = $this->buildMonster->buildMonster($this->monster, $characterStatReductionAffixes, $skillReduction, $resistanceReduction);

        $this->voidance->void($this->character, $this->characterCacheData, $monster, $isRankFight);

        $this->mergeMessages($this->voidance->getMessages());

        $this->mergeMessages($this->buildMonster->getMessages());

        $isPlayerVoided = $this->voidance->isPlayerVoided();
        $isEnemyVoided  = $this->voidance->isEnemyVoided();

        $ambush = $this->ambush->handleAmbush($this->character, $monster, $isPlayerVoided, $isRankFight);

        $health = $ambush->getHealthObject();

        $health['max_character_health']     = (int) $this->characterCacheData->getCachedCharacterData($this->character, 'health');
        $health['current_character_health'] = $health['current_character_health'] <= 0 ? 0 : $health['current_character_health'];
        $health['max_monster_health']       = $monster->getHealth();
        $health['current_monster_health']   = $health['current_monster_health'] <= 0 ? 0 : $health['current_monster_health'];

        $this->mergeMessages($this->ambush->getMessages());


        $data = [
            'health'                => $health,
            'player_voided'         => $isPlayerVoided,
            'enemy_voided'          => $isEnemyVoided,
            'monster'               => $monster->getMonster(),
            'opening_messages'      => $this->getBattleMessages(),
            'rank'                  => $rank,
        ];

        if ($isRankFight && ($data['health']['current_character_health'] > 0 && $data['health']['current_monster_health'] > 0)) {
            Cache::put('rank-fight-for-character-' . $this->character->id, $data);
        }

        return $data;
    }

    /**
     * Fight the monster.
     *
     * - Returns true if the character won.
     * - Returns false if the character lost or took too long or if neither side won.
     *
     * Use the methods here to determine based on health who won.
     *
     * @param bool $onlyOnce
     * @param bool $isRankFight
     * @param string|null $attackType
     * @return bool
     */
    public function fightMonster(bool $onlyOnce = false, bool $isRankFight = false, string $attackType = null): bool {

        if (!is_null($attackType)) {
            $this->attackType = $attackType;
        }

        if ($isRankFight) {
            if (Cache::has('rank-fight-for-character-' . $this->character->id)) {
                $data = Cache::get('rank-fight-for-character-' . $this->character->id);

                $this->rank    = $data['rank'];
                $this->monster = $data['monster'];
            } else {
                $data = $this->fightSetUp(true);

                $this->rank    = $data['rank'];
                $this->monster = $data['monster'];
            }
        } else if (Cache::has('monster-fight-' . $this->character->id)) {
            $data = Cache::get('monster-fight-' . $this->character->id);

            $this->monster = $data['monster'];
        } else {
            $data = $this->fightSetUp();

            $this->monster = $data['monster'];
        }

        return $this->processAttack($data, $onlyOnce, $isRankFight);
    }

    /**
     * Process the attack on the monster.
     *
     * @param array $data
     * @param boolean $onlyOnce
     * @param boolean $isRankFight
     * @return boolean
     */
    public function processAttack(array $data, bool $onlyOnce = false, $isRankFight = false): bool {

        $health         = $data['health'];
        $monster        = $this->buildMonster->setServerMonster(is_array($data['monster']) ? $data['monster'] : $data['monster']->getMonster())->setHealth($health['current_monster_health']);
        $isPlayerVoided = $data['player_voided'];
        $isEnemyVoided  = $data['enemy_voided'];

        if ($health['current_character_health'] <= 0) {
            $this->battleMessages[] = [
                'message' => 'The enemies ambush has slaughtered you!',
                'type'    => 'enemy-action',
            ];

            return false;
        }

        if ($health['current_monster_health'] <= 0) {
            $this->battleMessages[] = [
                'message' => 'Your ambush has slaughtered the enemy!',
                'type'    => 'enemy-action',
            ];

            return true;
        }

        return $this->doAttack($monster, $health, $isPlayerVoided, $isEnemyVoided, $onlyOnce, $isRankFight);
    }

    /**
     * Do the actual attack
     *
     * @param ServerMonster $monster
     * @param array $health
     * @param bool $isPlayerVoided
     * @param bool $isEnemyVoided
     * @param bool $onlyOnce
     * @param bool $isRankFight
     * @return bool
     */
    protected function doAttack(ServerMonster $monster, array $health, bool $isPlayerVoided, bool $isEnemyVoided, bool $onlyOnce, bool $isRankFight = false): bool {

        $this->attack->setHealth($health)
            ->setIsCharacterVoided($isPlayerVoided)
            ->setIsEnemyVoided($isEnemyVoided)
            ->onlyAttackOnce($onlyOnce)
            ->attack($this->character, $monster, $this->attackType, 'character', $isRankFight);

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

    /**
     * Fetch the monster.
     *
     * @param string $mapName
     * @param int $monsterId
     * @return array
     */
    protected function fetchMonster(string $mapName, int $monsterId): array {

        $regularMonster = $this->fetchRegularMonster($mapName, $monsterId);

        if (!is_null($regularMonster)) {
            return $regularMonster;
        }

        $celestial = $this->fetchCelestial($mapName, $monsterId);

        if (!is_null($celestial)) {
            return $celestial;
        }

        return [];
    }

    /**
     * Fetches a regular monster.
     *
     * @param string $mapName
     * @param int $monsterId
     * @return array|null
     */
    protected function fetchRegularMonster(string $mapName, int $monsterId): array|null {
        if (!Cache::has('monsters')) {
            resolve(BuildMonsterCacheService::class)->buildCache();
        }

        $monsters = Cache::get('monsters')[$mapName];

        $gameMap  = GameMap::where('name', $mapName)->first();

        if (is_null($gameMap)) {
            return null;
        }

        if ($gameMap->mapType()->isTheIcePlane()) {
            $canAccessPurgatory = $this->character->inventory->slots->where('item.effect', ItemEffectsValue::PURGATORY)->count() > 0;

            if ($canAccessPurgatory) {
                $monsters = $monsters['regular'];
            } else {
                $monsters = $monsters['easier'];
            }
        }

        foreach ($monsters as $monster) {
            if ($monster['id'] === $monsterId) {

                $serverMonster = $this->buildMonster->setServerMonster($monster);

                $elementalData = $serverMonster->getElementData();

                $monster['elemental_atonement'] = $elementalData;
                $monster['highest_element']     = $serverMonster->getHighestElementName($elementalData, $serverMonster->getHighestElementDamage($elementalData));

                return $monster;
            }
        }

        return null;
    }

    /**
     * Fetches a celestial.
     *
     * @param string $mapName
     * @param int $monsterId
     * @return array|null
     */
    protected function fetchCelestial(string $mapName, int $monsterId): array|null {
        if (!Cache::has('celestials')) {
            resolve(BuildMonsterCacheService::class)->buildCelesetialCache();
        }

        $monsters = Cache::get('celestials')[$mapName];

        foreach ($monsters as $monster) {
            if ($monster['id'] === $monsterId) {
                return $monster;
            }
        }

        return null;
    }

    /**
     * Fetch Rank Monster.
     *
     * @param int $monsterId
     * @param int $rank
     * @return array|null
     */
    protected function fetchRankMonster(int $monsterId, int $rank): array|null {
        if (!Cache::has('rank-monsters')) {
            resolve(BuildMonsterCacheService::class)->createRankMonsters();
        }

        $monsters = Cache::get('rank-monsters')[$rank];

        foreach ($monsters as $monster) {
            if ($monster['id'] === $monsterId) {
                return $monster;
            }
        }

        return null;
    }

    /**
     * Merges the battle messages.
     *
     * @param array $messages
     * @return void
     */
    protected function mergeMessages(array $messages) {
        $this->battleMessages = array_merge($this->battleMessages, $messages);
    }

    /**
     * Remove duplicate messages.
     *
     * @param array $array
     * @return void
     */
    protected function removeDuplicateMessages(array &$array) {
        $uniqueMessages = [];

        $array = array_reduce($array, function ($result, $item) use (&$uniqueMessages) {
            if (!in_array($item['message'], $uniqueMessages)) {
                $uniqueMessages[] = $item['message'];
                $result[] = $item;
            }
            return $result;
        }, []);
    }
}
