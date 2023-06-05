<?php

namespace  App\Game\Battle\Services;

use Exception;
use App\Flare\Models\Raid;
use App\Flare\Models\Monster;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\Character;
use Illuminate\Support\Facades\Cache;
use App\Game\Core\Traits\ResponseBuilder;
use App\Flare\Models\RaidBossParticipation;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Flare\ServerFight\Monster\BuildMonster;
use App\Game\Battle\Jobs\RaidBossRewardHandler;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Flare\Services\BuildMonsterCacheService;
use App\Game\Battle\Events\UpdateRaidBossHealth;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Battle\Events\UpdateRaidAttacksLeft;
use App\Flare\Builders\Character\CharacterCacheData;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
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
     * @var integer $raidBossCurrentHealth
     */
    private int $raidBossCurrentHealth;

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
                'boss_max_hp'       => $monsterHealth,
                'boss_current_hp'   => $monsterHealth,
                'raid_boss_deatils' => $serverMonster->getMonster(),
            ]);

            $raidBoss = $raidBoss->refresh();
        }

        $characterHealth = $character->getInformation()->buildHealth();

        $raidBossParticipation = RaidBossParticipation::where('character_id', $character->id)->first();

        return $this->successResult([
            'character_max_health'     => $characterHealth,
            'character_current_health' => $characterHealth,
            'monster_max_health'       => $raidBoss->boss_max_hp,
            'monster_current_health'   => $raidBoss->boss_current_hp,
            'attacks_left'             => !is_null($raidBossParticipation) ? $raidBossParticipation->attacks_left : 5,
            'is_raid_boss'             => true,
        ]);
    }

    /**
     * Set the current health for the raid battle service.
     *
     * @param integer $raidBossCurrentHealth
     * @return RaidBattleService
     */
    public function setRaidBossHealth(int $raidBossCurrentHealth): RaidBattleService {
        $this->raidBossCurrentHealth = $raidBossCurrentHealth;

        return $this;
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
            'attacks_left'             => 0,
            'is_raid_boss'             => false,
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

        if ($isRaidBoss) {
            $serverMonster->setHealth($this->raidBossCurrentHealth);
        }

        $monster   = $serverMonster->getMonster();
        
        $fightData = $this->getFightData($character, $serverMonster, $monsterId, $monster, $attackType, $isRaidBoss);
        
        $messages  = $this->monsterPlayerFight->getBattleMessages();

        if (!$this->hasCachedHealth($character->id, $monsterId)) {
            $preAttackResult = $this->handlePreAttack($character, $fightData['health'], $messages, $monsterId, $isRaidBoss);

            if (!empty($preAttackResult)) {

                return $this->successResult($preAttackResult);
            }
        }

        $result = $this->monsterPlayerFight->processAttack($fightData, true);

        $resultData = [
            'character_current_health' => $this->monsterPlayerFight->getCharacterHealth(),
            'monster_current_health'   => $this->monsterPlayerFight->getMonsterHealth(),
            'messages'                 => $this->monsterPlayerFight->getBattleMessages(),
        ];

        if (!$result && $this->monsterPlayerFight->getCharacterHealth() <= 0) {

            $this->handleRaidBossHealth($character, $monsterId, $isRaidBoss);

            $this->battleEventHandler->processDeadCharacter($character);

            $resultData['character_current_health'] = 0;

            $this->setCachedHealth($serverMonster, $fightData, $character->id, $monsterId, $resultData['monster_current_health']);

            return $this->successResult($resultData);
        }

        if ($this->monsterPlayerFight->getMonsterHealth() <= 0) {
            $this->handleRaidBossHealth($character, $monsterId, $isRaidBoss);

            $raid = Raid::where('raid_boss_id', $monsterId)->first();

            RaidBossRewardHandler::dispatch($character->id, $monsterId, is_null($raid) ? null : $raid->id);

            $resultData['monster_current_health'] = 0;

            $this->deleteMonsterCacheHealth($character->id, $monsterId);

            return $this->successResult($resultData);
        }

        $this->setCachedHealth($serverMonster, $fightData, $character->id, $monsterId, $resultData['monster_current_health']);

        $this->handleRaidBossHealth($character, $monsterId, $isRaidBoss);

        return $this->successResult($resultData);
    }

    /**
     * Get the fight data for the raid critter.
     *
     * @param Character $character
     * @param ServerMonster $serverMonster
     * @param integer $monsterId
     * @param array $monster
     * @param string $attackType
     * @param boolean $isRaidBoss
     * @return array
     */
    protected function getFightData(Character $character, ServerMonster $serverMonster, int $monsterId, array $monster, string $attackType, bool $isRaidBoss): array {
        if (!$isRaidBoss && $this->hasCachedHealth($character->id, $monsterId)) {
            $fightData = $this->getCachedFightData($character->id, $monsterId);
            $fightData['health']['monster_health'] = $serverMonster->getHealth();

            $this->monsterPlayerFight->setUpRaidFight($character, $monster, $attackType);
        } else {
            $fightData = $this->monsterPlayerFight->setUpRaidFight($character, $monster, $attackType)->fightSetUp();
        }

        if ($isRaidBoss) {
            $raidBoss = RaidBoss::where('raid_boss_id', $monsterId)->first();

            $fightData['monster']                  = resolve(ServerMonster::class)->setMonster($raidBoss->raid_boss_deatils)
                                                                                  ->setHealth($raidBoss->boss_current_hp);
            $fightData['health']['monster_health'] = $raidBoss->boss_current_hp;
        }

        return $fightData;
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

            $this->handleRaidBossHealth($character, $monsterId, $isRaidBoss);

            $this->battleEventHandler->processDeadCharacter($character);

            return $this->successResult();
        }

        if ($health['monster_health'] <= 0) {
            $health['monster_health'] = 0;

            $messages[] = [
                'message' => 'Your ambush has slaughtered the enemy!',
                'type'    => 'enemy-action',
            ];

            $this->handleRaidBossHealth($character, $monsterId, $isRaidBoss);

            $raid = Raid::where('raid_boss_id', $monsterId)->first();

            RaidBossRewardHandler::dispatch($character->id, $raid->id, $monsterId);

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
            // always get the latest and greates new health when updating.
            'boss_current_hp' => $raidBoss->refresh()->boss_current_hp < $newHealth ? $raidBoss->refresh()->boss_current_hp : $newHealth,
        ]);

        $raidBoss = $raidBoss->refresh();

        event(new UpdateRaidBossHealth($raidBoss->id, $raidBoss->boss_current_hp));
    }

    /**
     * Handle the raid boss health, assuming we are a raid monster.
     *
     * @param integer $monsterId
     * @param boolean $shouldUpdateHealth
     * @return void
     */
    protected function handleRaidBossHealth(Character $character, int $monsterId, bool $shouldUpdateHealth): void {

        if (!$shouldUpdateHealth) {
            return;
        }

        $raidBoss  = RaidBoss::where('raid_boss_id', $monsterId)->first();
        $oldHealth = $raidBoss->boss_current_hp;

        $this->updateRaidBossHealth($raidBoss, $this->monsterPlayerFight->getMonsterHealth());

        $this->updateRaidParticipation($character, $raidBoss, $oldHealth);
    }

    /**
     * Update raid Participation info.
     *
     * @param Character $character
     * @param RaidBoss $raidBoss
     * @param integer $oldHealth
     * @return void
     */
    private function updateRaidParticipation(Character $character, RaidBoss $raidBoss, int $oldHealth): void {
        $raidBossParticipation = RaidBossParticipation::where('character_id', $character->id)->first();

        $newHealth      = $raidBoss->refresh()->boss_current_hp;
        $damageDealt    = ($oldHealth - ($newHealth <= 0 ? 0 : $newHealth));
        $killedRaidBoss = $damageDealt >= $oldHealth;

        if (!is_null($raidBossParticipation)) {
            $attacksLeft     = $raidBossParticipation->attacks_left - 1;
            
            $newDamageAmount = $raidBossParticipation->damage_dealt + ($damageDealt >= $oldHealth ? $oldHealth : $damageDealt);
           
            $raidBossParticipation->update([
                'attacks_left' => $attacksLeft <= 0 ? 0 : $attacksLeft,
                'damage_dealt' => $newDamageAmount,
                'killed_boss'  => $killedRaidBoss,
            ]);

            if (!is_null($raidBossParticipation)) {
                event(new UpdateRaidAttacksLeft($character->user_id, ($attacksLeft <= 0 ? 0 : $attacksLeft)));
            }

            if ($killedRaidBoss) {
                event(new UpdateRaidAttacksLeft($character->user_id, 0));
            }

            return;
        }

        RaidBossParticipation::create([
            'character_id'  => $character->id,
            'raid_id'       => $raidBoss->raid->id,
            'attacks_left'  => 4,
            'damage_dealt'  => $damageDealt,
            'killed_boss'   => $killedRaidBoss,
        ]);

        if ($killedRaidBoss) {
            event(new UpdateRaidAttacksLeft($character->user_id, 0));

            return;
        }

        event(new UpdateRaidAttacksLeft($character->user_id, 4));
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

        return !is_null($raidBoss->boss_max_hp) && !is_null($raidBoss->boss_current_hp);
    }
}