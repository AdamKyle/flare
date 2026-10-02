<?php

namespace App\Game\Battle\ServerFight;

use App\Flare\Models\Character;
use App\Flare\Models\Map;
use App\Game\Battle\ServerFight\Fight\Ambush;
use App\Game\Battle\ServerFight\Fight\Attack;
use App\Game\Battle\ServerFight\Fight\Voidance;
use App\Game\Battle\ServerFight\Monster\BuildMonster;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Combat\Values\ElementAttackData;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Exploration\Services\DelveMonsterService;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use App\Game\Monsters\Services\MonsterListService;
use App\Game\Monsters\Values\MonsterCacheKey;
use Illuminate\Support\Facades\Cache;

class MonsterPlayerFight
{
    use ResponseBuilder;

    private array $monster;

    private array $battleMessages;

    private string $attackType;

    private int $rank = 0;

    private bool $tookTooLong;

    private Character $character;

    private BuildMonster $buildMonster;

    private CharacterCacheData $characterCacheData;

    private DelveMonsterService $delveMonsterService;

    private Voidance $voidance;

    private Ambush $ambush;

    private Attack $attack;

    private ?int $forcedCurrentMonsterHealth = null;

    private ?int $forcedMaxMonsterHealth = null;

    private BuildMonsterCacheService $buildMonsterCacheService;

    private ElementAttackData $elementAttackData;

    private MonsterListService $monsterListService;

    /**
     * @param BuildMonster $buildMonster
     * @param CharacterCacheData $characterCacheData
     * @param DelveMonsterService $delveMonsterService
     * @param Voidance $voidance
     * @param Ambush $ambush
     * @param Attack $attack
     * @param BuildMonsterCacheService $buildMonsterCacheService
     * @param ElementAttackData $elementAttackData
     * @param MonsterListService $monsterListService
     */
    public function __construct(
        BuildMonster $buildMonster,
        CharacterCacheData $characterCacheData,
        DelveMonsterService $delveMonsterService,
        Voidance $voidance,
        Ambush $ambush,
        Attack $attack,
        BuildMonsterCacheService $buildMonsterCacheService,
        ElementAttackData $elementAttackData,
        MonsterListService $monsterListService,
    ) {
        $this->buildMonster = $buildMonster;
        $this->characterCacheData = $characterCacheData;
        $this->delveMonsterService = $delveMonsterService;
        $this->voidance = $voidance;
        $this->ambush = $ambush;
        $this->attack = $attack;
        $this->buildMonsterCacheService = $buildMonsterCacheService;
        $this->elementAttackData = $elementAttackData;
        $this->monsterListService = $monsterListService;
        $this->battleMessages = [];
        $this->tookTooLong = false;
    }

    /**
     * Set the Character for a fight whose setup already exists in the cache.
     *
     * @param Character $character
     * @return MonsterPlayerFight
     */
    public function setCharacter(Character $character): MonsterPlayerFight
    {
        $this->character = $character;
        $this->forcedCurrentMonsterHealth = null;
        $this->forcedMaxMonsterHealth = null;

        return $this;
    }

    /**
     * Set up the fight, returning an error result when the Monster cannot be found.
     *
     * @param Character $character
     * @param array $params
     * @param bool $shouldIncreaseStrength
     * @return MonsterPlayerFight|array
     */
    public function setUpFight(Character $character, array $params, bool $shouldIncreaseStrength = false): MonsterPlayerFight|array
    {

        $this->character = $character;
        $this->monster = $params['cached_monster'] ?? $this->fetchMonster($character, $params['selected_monster_id']);

        $this->attackType = $params['attack_type'];
        $this->forcedCurrentMonsterHealth = $params['current_monster_health'] ?? null;
        $this->forcedMaxMonsterHealth = $params['max_monster_health'] ?? null;

        if (empty($this->monster)) {
            return $this->errorResult('No monster was found.');
        }

        if (! $shouldIncreaseStrength || isset($params['cached_monster'])) {
            return $this;
        }

        $this->monster = $this->delveMonsterService->createMonster($this->monster, $character);

        if ($params['pack_size'] > 1) {
            Cache::put('delve-monster-'.$character->id.'-'.$this->monster['id'].'-fight', $this->monster, 900);
        }

        return $this;
    }

    /**
     * Set up a raid fight against the supplied raid Monster payload.
     *
     * @param Character $character
     * @param array $raidMonster
     * @param string $attackType
     * @return MonsterPlayerFight
     */
    public function setUpRaidFight(Character $character, array $raidMonster, string $attackType): MonsterPlayerFight
    {

        $this->monster = $raidMonster;
        $this->character = $character;
        $this->attackType = $attackType;
        $this->forcedCurrentMonsterHealth = null;
        $this->forcedMaxMonsterHealth = null;

        return $this;
    }

    /**
     * Delete the cached Character sheet data for the Character.
     *
     * @param Character $character
     * @return void
     */
    public function deleteCharacterCache(Character $character): void
    {
        $this->characterCacheData->deleteCharacterSheet($character);
    }

    /**
     * Return the accumulated battle messages.
     *
     * @return array
     */
    public function getBattleMessages(): array
    {
        $messages = $this->battleMessages;

        return $messages;
    }

    /**
     * Reset the battle messages of this fight and its collaborators.
     *
     * @return void
     */
    public function resetBattleMessages(): void
    {
        $this->battleMessages = [];

        $this->voidance->clearMessages();
        $this->ambush->clearMessages();
        $this->attack->resetBattleMessages();
    }

    /**
     * Return the Monster payload for this fight.
     *
     * @return array
     */
    public function getMonster(): array
    {
        return $this->monster;
    }

    /**
     * Return the Character's current fight health.
     *
     * @return int
     */
    public function getCharacterHealth(): int
    {
        return $this->attack->getCharacterHealth();
    }

    /**
     * Return the Monster's current fight health.
     *
     * @return int
     */
    public function getMonsterHealth(): int
    {
        return $this->attack->getMonsterHealth();
    }

    /**
     * Return the Monster's most recently rolled attack.
     *
     * @return int
     */
    public function getMonsterLastRolledAttack(): int
    {
        return $this->attack->getMonsterLastRolledAttack();
    }

    /**
     * Build the Monster, apply voidance and ambush, and return the initial fight state.
     *
     * @return array
     */
    public function fightSetUp(): array
    {
        $characterStatReductionAffixes = $this->characterCacheData->getCachedCharacterData($this->character, 'stat_affixes');
        $skillReduction = $this->characterCacheData->getCachedCharacterData($this->character, 'skill_reduction');
        $resistanceReduction = $this->characterCacheData->getCachedCharacterData($this->character, 'resistance_reduction');

        $monster = $this->buildMonster->buildMonster($this->monster, $characterStatReductionAffixes, $skillReduction, $resistanceReduction);

        if (! is_null($this->forcedCurrentMonsterHealth)) {
            $currentMonsterHealth = min($this->forcedCurrentMonsterHealth, $this->forcedMaxMonsterHealth ?? $this->forcedCurrentMonsterHealth);

            $monster->setHealth(max($currentMonsterHealth, 0));
        }

        $this->voidance->void($this->character, $this->characterCacheData, $monster);

        $this->mergeMessages($this->voidance->getMessages());

        $this->mergeMessages($this->buildMonster->getMessages());

        $isPlayerVoided = $this->voidance->isPlayerVoided();
        $isEnemyVoided = $this->voidance->isEnemyVoided();

        $ambush = $this->ambush->handleAmbush($this->character, $monster, $isPlayerVoided, $isEnemyVoided);

        $health = $ambush->getHealthObject();

        $health['max_character_health'] = $this->getMaxCharacterHealth();
        $health['current_character_health'] = max($health['current_character_health'], 0);
        $health['max_monster_health'] = $this->forcedMaxMonsterHealth ?? $monster->getHealth();
        $health['current_monster_health'] = max($health['current_monster_health'], 0);

        if (! is_null($this->forcedMaxMonsterHealth)) {
            $health['current_monster_health'] = min($health['current_monster_health'], $this->forcedMaxMonsterHealth);
        }

        $this->mergeMessages($this->ambush->getMessages());

        return [
            'health' => $health,
            'attack_messages' => $this->getBattleMessages(),
            'monster_id' => $monster->getId(),
            'monster' => $monster->getMonster(),
            'player_voided' => $isPlayerVoided,
            'enemy_voided' => $isEnemyVoided,
        ];
    }

    /**
     * Fight the Monster, returning true only when the Character won.
     *
     * @param bool $onlyOnce
     * @param string|null $attackType
     * @return bool
     */
    public function fightMonster(bool $onlyOnce = false, ?string $attackType = null): bool
    {
        if (! is_null($attackType)) {
            $this->attackType = $attackType;
        }

        $hasCachedFight = Cache::has('monster-fight-'.$this->character->id) && is_null($this->forcedCurrentMonsterHealth);

        $data = $hasCachedFight ? Cache::get('monster-fight-'.$this->character->id) : $this->fightSetUp();

        $this->monster = $data['monster'];

        return $this->processAttack($data, $onlyOnce);
    }

    /**
     * Process one attack exchange from the supplied fight state, returning true only when the Character won.
     *
     * @param array $data
     * @param bool $onlyOnce
     * @return bool
     */
    public function processAttack(array $data, bool $onlyOnce = false): bool
    {

        $health = $data['health'];
        $monster = $this->buildMonster->setServerMonster(is_array($data['monster']) ? $data['monster'] : $data['monster']->getMonster())->setHealth($health['current_monster_health']);
        $isPlayerVoided = $data['player_voided'];
        $isEnemyVoided = $data['enemy_voided'];

        $this->attack = $this->attack->setHealth($health)
            ->setIsCharacterVoided($isPlayerVoided)
            ->setIsEnemyVoided($isEnemyVoided)
            ->onlyAttackOnce($onlyOnce);

        if ($health['current_character_health'] <= 0) {
            $this->battleMessages[] = [
                'message' => 'The enemies ambush has slaughtered you!',
                'type' => 'enemy-action',
            ];

            return false;
        }

        if ($health['current_monster_health'] <= 0) {
            $this->battleMessages[] = [
                'message' => 'Your ambush has slaughtered the enemy!',
                'type' => 'enemy-action',
            ];

            return true;
        }

        return $this->doAttack($monster);
    }

    /**
     * Run the attack against the server Monster, returning true only when the Character won.
     *
     * @param ServerMonster $monster
     * @return bool
     */
    private function doAttack(ServerMonster $monster): bool
    {

        $this->attack
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
     * Fetch the Monster the Character is fighting, using the same contextual cache as the Monster list.
     *
     * @param Character $character
     * @param int $monsterId
     * @return array
     */
    private function fetchMonster(Character $character, int $monsterId): array
    {
        $regularMonster = $this->monsterListService->getMonsterForFight($character, $monsterId);

        if (! is_null($regularMonster)) {
            return $this->augmentWithElementalData($regularMonster);
        }

        $celestial = $this->fetchCelestial($character->map, $monsterId);

        if (! is_null($celestial)) {
            return $celestial;
        }

        return [];
    }

    /**
     * Augment a cached Monster payload with the elemental atonement and penetration data needed for combat.
     *
     * @param array $monster
     * @return array
     */
    private function augmentWithElementalData(array $monster): array
    {
        $serverMonster = $this->buildMonster->setServerMonster($monster);

        $elementalData = $serverMonster->getElementData();

        $monster['elemental_atonement'] = $elementalData;
        $monster['highest_element'] = $this->elementAttackData->getHighestElementName($elementalData, $this->elementAttackData->getHighestElementDamage($elementalData));
        $monster['elemental_penetration'] = $serverMonster->getElementalPenetrationData();

        return $monster;
    }

    /**
     * Fetch a cached Celestial on the Character's map.
     *
     * @param Map $map
     * @param int $monsterId
     * @return array|null
     */
    private function fetchCelestial(Map $map, int $monsterId): ?array
    {
        if (! Cache::has(MonsterCacheKey::CELESTIALS->value)) {
            $this->buildMonsterCacheService->buildCelestialCache();
        }

        $mapName = $map->gameMap->name;

        $monsters = Cache::get(MonsterCacheKey::CELESTIALS->value)[$mapName]['data'];

        foreach ($monsters as $monster) {
            if ($monster['id'] === $monsterId) {
                return $monster;
            }
        }

        return null;
    }

    /**
     * Append the supplied messages to the battle messages.
     *
     * @param array $messages
     * @return void
     */
    private function mergeMessages(array $messages): void
    {
        $this->battleMessages = array_merge($this->battleMessages, $messages);
    }

    /**
     * Fetch the Character's max health from the Character sheet cache as an integer.
     *
     * @return int
     */
    private function getMaxCharacterHealth(): int
    {
        return intval($this->characterCacheData->getCachedCharacterData($this->character, 'health'));
    }
}
