<?php

namespace  App\Game\Battle\Services;

use Exception;
use App\Flare\Models\Monster;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\Character;
use Illuminate\Support\Facades\Cache;
use App\Game\Core\Traits\ResponseBuilder;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Flare\ServerFight\Monster\BuildMonster;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Flare\Services\BuildMonsterCacheService;
use App\Game\Battle\Events\UpdateRaidBossHealth;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Flare\Builders\Character\CharacterCacheData;
use Facade\App\Game\Messages\Handlers\ServerMessageHandler;
use App\Game\Battle\Services\Concerns\HandleCachedRaidCritterHealth;

class RaidBattleService {

    use ResponseBuilder, HandleCachedRaidCritterHealth;

    /**
     * @var BuildMonster $buildMonster
     */
    private BuildMonster $buildMonster;

    /**
     * @var CharacterCacheData $characterCacheData
     */
    private CharacterCacheData $characterCacheData;

    /**
     * @var MonsterPlayerFight $monsterPlayerFight
     */
    private MonsterPlayerFight $monsterPlayerFight;

    /**
     * @var BuildMonsterCacheService $buildMonsterCacheService
     */
    private BuildMonsterCacheService $buildMonsterCacheService;

    /**
     * @var BattleEventHandler $battleEventHandler
     */
    private BattleEventHandler $battleEventHandler;

    /**
     * @param BuildMonster $buildMonster
     * @param CharacterCacheData $characterCacheData
     * @param MonsterPlayerFight $monsterPlayerFight
     * @param BuildMonsterCacheService $buildMonsterCacheService
     * @param BattleEventHandler $battleEventHandler
     */
    public function __construct(BuildMonster $buildMonster, 
                                CharacterCacheData $characterCacheData, 
                                MonsterPlayerFight $monsterPlayerFight,
                                BuildMonsterCacheService $buildMonsterCacheService,
                                BattleEventHandler $battleEventHandler,
    ) {
        $this->buildMonster             = $buildMonster;
        $this->characterCacheData       = $characterCacheData;
        $this->monsterPlayerFight       = $monsterPlayerFight;
        $this->buildMonsterCacheService = $buildMonsterCacheService;
        $this->battleEventHandler       = $battleEventHandler;

    }

    /**
     * Set up the boss battle.
     *
     * @param Character $character
     * @param RaidBoss $raidBoss
     * @return array
     */
    public function setUpRaidBossBattle(Character $character, RaidBoss $raidBoss): array {

        try {
            $serverMonster = $this->buildServerMonster($character, $raidBoss->raid_boss_id);
        } catch (Exception $e) {
            return $this->errorResult($e->getMessage());
        }

        if (!$this->isRaidBossSetup($raidBoss)) {
            $monsterHealth   = $serverMonster->getHealth();

            $raidBoss->update([
                'boss_max_hp'     => $monsterHealth,
                'boss_current_hp' => $monsterHealth,
            ]);

            $raidBoss = $raidBoss->refresh();
        }

        $characterHealth = $character->getInformation()->buildHealth();

        return $this->successResult([
            'character_max_health'     => $characterHealth,
            'character_current_health' => $characterHealth,
            'monster_max_health'       => $raidBoss->boss_max_hp,
            'monster_current_health'   => $raidBoss->boss_current_hp,
        ]);
    }

    /**
     * Set up the raid critter monster.
     *
     * @param Character $character
     * @param Monster $monster
     * @return array
     */
    public function setUpRaidCritterMonster(Character $character, Monster $monster): array {
        try {
            $serverMonster   = $this->buildServerMonster($character, $monster->id);
        } catch (Exception $e) {
            return $this->errorResult($e->getMessage());
        }

        $characterHealth = $character->getInformation()->buildHealth();

        $monsterHealth   = $serverMonster->getHealth();

        return $this->successResult([
            'character_max_health'     => $characterHealth,
            'character_current_health' => $characterHealth,
            'monster_max_health'       => $monsterHealth,
            'monster_current_health'   => $monsterHealth,
        ]);
    }

    /**
     * Fight either the raid boss or the raid critter.
     *
     * @param Character $character
     * @param integer $monsterId
     * @param string $attackType
     * @param boolean $isRaidBoss
     * @return array
     */
    public function fightRaidMonster(Character $character, int $monsterId, string $attackType, bool $isRaidBoss = false): array {

        try {
            $serverMonster = $this->buildServerMonster($character, $monsterId);
        } catch (Exception $e) {
            return $this->errorResult($e->getMessage());
        }

        if (!$isRaidBoss && $this->hasCachedHealth($character->id, $monsterId)) {
            $serverMonster->setHealth($this->getCachedHealth($character->id, $monsterId));
        }

        $monster   = $serverMonster->getMonster();
        
        if (!$isRaidBoss && $this->hasCachedHealth($character->id, $monsterId)) {
            $fightData = $this->getCachedFightData($character->id, $monsterId);
            $fightData['health']['monster_health'] = $serverMonster->getHealth();

            $this->monsterPlayerFight->setUpRaidFight($character, $monster, $attackType);
        } else {
            $fightData = $this->monsterPlayerFight->setUpRaidFight($character, $monster, $attackType)->fightSetUp();
        }
        
        $messages  = $this->monsterPlayerFight->getBattleMessages();

        if (!$this->hasCachedHealth($character->id, $monsterId)) {
            $preAttackResult = $this->handlePreAttack($character, $fightData['health'], $messages, $monsterId, $isRaidBoss);
            dump($preAttackResult);
            if (!empty($preAttackResult)) {

                return $this->successResult($preAttackResult);
            }
        }

        dump('Health before fight: ');
        dump($fightData['health']);

        $result = $this->monsterPlayerFight->processAttack($fightData, true);

        dump('Monster hp after fight: ' . $this->monsterPlayerFight->getMonsterHealth());

        $resultData = [
            'character_current_health' => $this->monsterPlayerFight->getCharacterHealth(),
            'monster_current_health'   => $this->monsterPlayerFight->getMonsterHealth(),
            'messages'                 => $this->monsterPlayerFight->getBattleMessages(),
        ];

        if (!$result && $this->monsterPlayerFight->getCharacterHealth() <= 0) {

            $this->handleRaidBossHealth($monsterId, $isRaidBoss);

            $this->battleEventHandler->processDeadCharacter($character);

            $resultData['character_current_health'] = 0;

            $this->setCachedHealth($serverMonster, $fightData, $character->id, $monsterId, $resultData['monster_current_health']);

            return $this->successResult($resultData);
        }

        if ($this->monsterPlayerFight->getMonsterHealth() <= 0) {
            $this->handleRaidBossHealth($monsterId, $isRaidBoss);

            $this->battleEventHandler->processMonsterDeath($character->id, $monsterId);

            $resultData['monster_current_health'] = 0;

            $this->deleteMonsterCacheHealth($character->id, $monsterId);

            return $this->successResult($resultData);
        }

        $this->setCachedHealth($serverMonster, $fightData, $character->id, $monsterId, $resultData['monster_current_health']);

        $this->handleRaidBossHealth($monsterId, $isRaidBoss);

        return $this->successResult($resultData);
    }


    /**
     * Process the pre attack.
     * 
     * This could mean the character is deasd, the monster is dead.
     *
     * @param Character $character
     * @param RaidBoss $raidBoss
     * @param array $health
     * @param array $messages
     * @return array
     */
    protected function handlePreAttack(Character $character, array $health, array $messages, int $monsterId, bool $isRaidBoss = false): array {
        if ($health['character_health'] <= 0) {
            $health['character_health'] = 0;

            $messages[] = [
                'message' => 'The enemies ambush has slaughtered you!',
                'type'    => 'enemy-action',
            ];

            $this->handleRaidBossHealth($monsterId, $isRaidBoss);

            $this->battleEventHandler->processDeadCharacter($character);

            return $this->successResult();
        }

        if ($health['monster_health'] <= 0) {
            $health['monster_health'] = 0;

            $messages[] = [
                'message' => 'Your ambush has slaughtered the enemy!',
                'type'    => 'enemy-action',
            ];

            $this->handleRaidBossHealth($monsterId, $isRaidBoss);

            $this->battleEventHandler->processMonsterDeath($character->id, $monsterId);

            return $this->successResult();
        }

        return [];
    }

    /**
     * Update the raid bosses health.
     *
     * @param RaidBoss $raidBoss
     * @param integer $newHealth
     * @return void
     */
    protected function updateRaidBossHealth(RaidBoss $raidBoss, int $newHealth) {
        $raidBoss->update([
            'boss_current_hp' => $newHealth
        ]);

        $raidBoss = $raidBoss->refresh();

        event(new UpdateRaidBossHealth($raidBoss->id, $raidBoss->boss_current_health));
    }

    /**
     * Handle the raid boss health, assuming we are a raid monster.
     *
     * @param integer $monsterId
     * @param boolean $shouldUpdateHealth
     * @return void
     */
    protected function handleRaidBossHealth(int $monsterId, bool $shouldUpdateHealth): void {

        if (!$shouldUpdateHealth) {
            return;
        }

        $raidBoss = RaidBoss::where('raid_boss_id', $monsterId)->first();

        $this->updateRaidBossHealth($raidBoss, $this->monsterPlayerFight->getMonsterHealth());
    }

    /**
     * Build the server monster.
     *
     * @param Character $character
     * @param integer $monsterId
     * @return ServerMonster
     */
    private function buildServerMonster(Character $character, int $monsterId): ServerMonster {
        $characterStatReductionAffixes = $this->characterCacheData->getCachedCharacterData($character, 'stat_affixes');
        $skillReduction                = $this->characterCacheData->getCachedCharacterData($character, 'skill_reduction');
        $resistanceReduction           = $this->characterCacheData->getCachedCharacterData($character, 'resistance_reduction');

        $cache = Cache::get('raid-monsters');

        if (is_null($cache)) {
            $this->buildMonsterCacheService->buildRaidCache();

            $cache = Cache::get('raid-monsters');
        }

        $raidMonsters = $cache[$character->map->gameMap->name];
        $monster      = [];

        foreach ($raidMonsters as $raidMonster) {
            if ($raidMonster['id'] === $monsterId) {
                $monster = $raidMonster;
                break;
            }
        }

        if (empty($monster)) {
            ServerMessageHandler::sendBasicMessage($character->user, 'Christ child! Something is horribly wrong with raid fights. Hover over your user icon and select discord (top roght) and tell The Creator!');

            throw new Exception('Failed to fetch raid monster from cache.');
        }

        return $this->buildMonster->buildMonster($monster, $characterStatReductionAffixes, $skillReduction, $resistanceReduction);
    }

    /**
     * Is the raid boss set up?
     *
     * @param RaidBoss $raidBoss
     * @return boolean
     */
    private function isRaidBossSetup(RaidBoss $raidBoss): bool {

        return is_null($raidBoss->boss_max_hp) && is_null($raidBoss->boss_current_hp);
    }
}
