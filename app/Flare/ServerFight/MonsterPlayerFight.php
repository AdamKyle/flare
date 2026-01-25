<?php

namespace App\Flare\ServerFight;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\ServerFight\Fight\Ambush;
use App\Flare\ServerFight\Fight\Attack;
use App\Flare\ServerFight\Fight\Voidance;
use App\Flare\ServerFight\Monster\BuildMonster;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Flare\Services\BuildMonsterCacheService;
use App\Flare\Services\DwelveMonsterService;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Traits\ResponseBuilder;
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

    private DwelveMonsterService $dwelveMonsterService;

    private Voidance $voidance;

    private Ambush $ambush;

    private Attack $attack;

    public function __construct(
        BuildMonster $buildMonster,
        CharacterCacheData $characterCacheData,
        DwelveMonsterService $dwelveMonsterService,
        Voidance $voidance,
        Ambush $ambush,
        Attack $attack
    ) {
        $this->buildMonster = $buildMonster;
        $this->characterCacheData = $characterCacheData;
        $this->dwelveMonsterService = $dwelveMonsterService;
        $this->voidance = $voidance;
        $this->ambush = $ambush;
        $this->attack = $attack;
        $this->battleMessages = [];
        $this->tookTooLong = false;
    }

    /**
     * Set the character.
     *
     * - Useful for rank fights where the setup is already done and in the cache.
     */
    public function setCharacter(Character $character): MonsterPlayerFight
    {
        $this->character = $character;

        return $this;
    }

    /**
     * Set up the fight.
     *
     * - Can return an error if the monster is not found.
     *
     * @return array|$this
     */
    public function setUpFight(Character $character, array $params, bool $shouldIncreaseStrength = false): MonsterPlayerFight|array
    {
        $this->character = $character;
        $this->monster = $this->fetchMonster($character->map, $params['selected_monster_id']);

        if ($shouldIncreaseStrength) {
            $this->monster = $this->dwelveMonsterService->createMonster($this->monster, $character);
        }

        $this->attackType = $params['attack_type'];

        if (empty($this->monster)) {
            return $this->errorResult('No monster was found.');
        }

        return $this;
    }

    /**
     * Set up  the raid fight
     */
    public function setUpRaidFight(Character $character, array $raidMonster, string $attackType): MonsterPlayerFight
    {

        $this->monster = $raidMonster;
        $this->character = $character;
        $this->attackType = $attackType;

        return $this;
    }

    /**
     * Did the fight take too long?
     */
    public function getTookTooLong(): bool
    {
        return $this->tookTooLong;
    }

    /**
     * Delete the cache data for the character
     */
    public function deleteCharacterCache(Character $character): void
    {
        $this->characterCacheData->deleteCharacterSheet($character);
    }

    /**
     * Get the battle messages.
     */
    public function getBattleMessages(): array
    {
        $messages = $this->battleMessages;

        // $this->removeDuplicateMessages($messages);

        return $messages;
    }

    /**
     * Reset all battle messages.
     */
    public function resetBattleMessages(): void
    {
        $this->battleMessages = [];

        $this->voidance->clearMessages();
        $this->ambush->clearMessages();
        $this->attack->resetBattleMessages();
    }

    /**
     * get the monster.
     */
    public function getMonster(): array
    {
        return $this->monster;
    }

    /**
     * Get the character health.
     */
    public function getCharacterHealth(): int
    {
        return $this->attack->getCharacterHealth();
    }

    /**
     * Get the monster health.
     */
    public function getMonsterHealth(): int
    {
        return $this->attack->getMonsterHealth();
    }

    /**
     * Base Fight Setup.
     */
    public function fightSetUp(): array
    {
        $characterStatReductionAffixes = $this->characterCacheData->getCachedCharacterData($this->character, 'stat_affixes');
        $skillReduction = $this->characterCacheData->getCachedCharacterData($this->character, 'skill_reduction');
        $resistanceReduction = $this->characterCacheData->getCachedCharacterData($this->character, 'resistance_reduction');

        $monster = $this->buildMonster->buildMonster($this->monster, $characterStatReductionAffixes, $skillReduction, $resistanceReduction);

        $this->voidance->void($this->character, $this->characterCacheData, $monster);

        $this->mergeMessages($this->voidance->getMessages());

        $this->mergeMessages($this->buildMonster->getMessages());

        $isPlayerVoided = $this->voidance->isPlayerVoided();
        $isEnemyVoided = $this->voidance->isEnemyVoided();

        $ambush = $this->ambush->handleAmbush($this->character, $monster, $isPlayerVoided);

        $health = $ambush->getHealthObject();

        $health['max_character_health'] = (int) $this->characterCacheData->getCachedCharacterData($this->character, 'health');
        $health['current_character_health'] = max($health['current_character_health'], 0);
        $health['max_monster_health'] = $monster->getHealth();
        $health['current_monster_health'] = max($health['current_monster_health'], 0);

        $this->mergeMessages($this->ambush->getMessages());

        $elementalAtonement = $monster->getElementData();
        $highestElement = $monster->getHighestElementName($elementalAtonement, $monster->getHighestElementDamage($elementalAtonement));

        $monster = $monster->getMonster();

        if (! empty($elementalAtonement)) {
            $monster['elemental_atonement'] = $elementalAtonement;
            $monster['highest_element'] = $highestElement;
        }

        return [
            'health' => $health,
            'player_voided' => $isPlayerVoided,
            'enemy_voided' => $isEnemyVoided,
            'monster' => $monster,
            'opening_messages' => $this->getBattleMessages(),
        ];
    }

    /**
     * Fight the monster.
     *
     * - Returns true if the character won.
     * - Returns false if the character lost or took too long or if neither side won.
     *
     * Use the methods here to determine based on health who won.
     */
    public function fightMonster(bool $onlyOnce = false, ?string $attackType = null): bool
    {

        if (! is_null($attackType)) {
            $this->attackType = $attackType;
        }

        if (Cache::has('monster-fight-' . $this->character->id)) {
            $data = Cache::get('monster-fight-' . $this->character->id);

            $this->monster = $data['monster'];

        } else {

            $data = $this->fightSetUp();

            $this->monster = $data['monster'];
        }

        return $this->processAttack($data, $onlyOnce);
    }

    /**
     * Process the attack on the monster.
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
     * Do the actual attack
     */
    protected function doAttack(ServerMonster $monster): bool
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
     * Fetch the monster.
     */
    protected function fetchMonster(Map $map, int $monsterId): array
    {
        $regularMonster = $this->fetchRegularMonster($map, $monsterId);

        if (! is_null($regularMonster)) {
            return $regularMonster;
        }

        $celestial = $this->fetchCelestial($map, $monsterId);

        if (! is_null($celestial)) {
            return $celestial;
        }

        $locationBasedMonster = $this->fetchLocationTypeSpecialMonster($map, $monsterId);

        if (! is_null($locationBasedMonster)) {

            return $locationBasedMonster;
        }

        return [];
    }

    /**
     * Fetches a regular monster.
     */
    protected function fetchRegularMonster(Map $map, int $monsterId): ?array
    {
        if (! Cache::has('monsters')) {
            resolve(BuildMonsterCacheService::class)->buildCache();
        }

        $mapName = $map->gameMap->name;

        $monsters = Cache::get('monsters')[$mapName];

        $gameMap = GameMap::where('name', $mapName)->first();

        if (is_null($gameMap)) {
            return null;
        }

        if ($gameMap->mapType()->isTheIcePlane() || $gameMap->mapType()->isDelusionalMemories()) {
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
                $monster['highest_element'] = $serverMonster->getHighestElementName($elementalData, $serverMonster->getHighestElementDamage($elementalData));

                return $monster;
            }
        }

        return null;
    }

    /**
     * Fetches a celestial.
     */
    protected function fetchCelestial(Map $map, int $monsterId): ?array
    {
        if (! Cache::has('celestials')) {
            resolve(BuildMonsterCacheService::class)->buildCelesetialCache();
        }

        $mapName = $map->gameMap->name;

        $monsters = Cache::get('celestials')[$mapName];

        foreach ($monsters as $monster) {
            if ($monster['id'] === $monsterId) {
                return $monster;
            }
        }

        return null;
    }

    /**
     * Fetch monster for a special location.
     */
    protected function fetchLocationTypeSpecialMonster(Map $map, int $monsterId): ?array
    {

        $locationWithType = Location::whereNotNull('type')
            ->where('x', $map->character_position_x)
            ->where('y', $map->character_position_y)
            ->where('game_map_id', $map->game_map_id)
            ->first();

        if (is_null($locationWithType)) {
            return null;
        }

        $monstersForLocation = Cache::get('special-location-monsters');

        if (isset($monstersForLocation['location-type-' . $locationWithType->type])) {
            $monsters = $monstersForLocation['location-type-' . $locationWithType->type];

            foreach ($monsters as $monster) {
                if ($monster['id'] === $monsterId) {
                    return $monster;
                }
            }
        }

        return null;
    }

    /**
     * Merges the battle messages.
     */
    protected function mergeMessages(array $messages): void
    {
        $this->battleMessages = array_merge($this->battleMessages, $messages);
    }
}
