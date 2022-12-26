<?php

namespace App\Flare\ServerFight;

use App\Flare\ServerFight\Monster\ServerMonster;
use Illuminate\Support\Facades\Cache;
use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\Ambush;
use App\Flare\ServerFight\Fight\Attack;
use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\ServerFight\Fight\Voidance;
use App\Flare\ServerFight\Monster\BuildMonster;
use App\Flare\Services\BuildMonsterCacheService;
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
    public function __construct(BuildMonster $buildMonster, CharacterCacheData $characterCacheData, Voidance $voidance, Ambush $ambush, Attack $attack) {
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
        return $this->battleMessages;
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
     * Base Fight Setup.
     *
     * @param bool $isRankFight
     * @return array
     */
    public function fightSetUp(bool $isRankFight = false): array {
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

        $this->mergeMessages($ambush->getMessages());

        $health = $ambush->getHealthObject();

        $health['max_character_health'] = $this->characterCacheData->getCachedCharacterData($this->character, 'health');
        $health['max_monster_health']   = $monster->getHealth();

        $data = [
            'health'        => $health,
            'player_voided' => $isPlayerVoided,
            'enemy_voided'  => $isEnemyVoided,
            'monster'       => $monster,
        ];

        if ($isRankFight && ($data['health']['character_health'] > 0 && $data['health']['monster_health'] > 0)) {
            Cache::put('rank-fight-for-character-' . $this->character->id, $data);
        }

        return $data;
    }

    /**
     * Fight the monster.
     *
     * - Returns true if the character won.
     * - Returns false if the character lost or took too long.
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

                $this->monster = $data['monster']->getMonster();
            } else {
                $data = $this->fightSetUp(true);

                $this->monster = $data['monster']->getMonster();
            }
        } else {
            $data = $this->fightSetUp();

            $this->monster = $data['monster']->getMonster();
        }

        $health         = $data['health'];
        $monster        = $data['monster'];
        $isPlayerVoided = $data['player_voided'];
        $isEnemyVoided  = $data['enemy_voided'];

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

        return $this->doAttack($monster, $health, $isPlayerVoided, $isEnemyVoided, $onlyOnce);
    }

    /**
     * Do the actual attack
     *
     * @param ServerMonster $monster
     * @param array $health
     * @param bool $isPlayerVoided
     * @param bool $isEnemyVoided
     * @param bool $onlyOnce
     * @return bool
     */
    protected function doAttack(ServerMonster $monster, array $health, bool $isPlayerVoided, bool $isEnemyVoided, bool $onlyOnce): bool {
        $this->attack->setHealth($health)
            ->setIsCharacterVoided($isPlayerVoided)
            ->setIsEnemyVoided($isEnemyVoided)
            ->onlyAttackOnce($onlyOnce)
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

        foreach ($monsters as $monster) {
            if ($monster['id'] === $monsterId) {
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
        $this->battleMessages = [...$this->battleMessages, ...$messages];
    }
}
