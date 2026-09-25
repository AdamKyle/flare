<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\RaidBossParticipation;
use App\Game\Battle\Events\UpdateRaidAttacksLeft;
use App\Game\Battle\Events\UpdateRaidBossHealth;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\ServerFight\Monster\BuildMonster;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Battle\ServerFight\MonsterPlayerFight;
use App\Game\Battle\Services\Concerns\HandleCachedRaidCritterHealth;
use App\Game\BattleRewardProcessing\Jobs\BattleAttackHandler;
use App\Game\BattleRewardProcessing\Jobs\RaidBossRewardHandler;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Support\Facades\Cache;

class RaidBattleService
{
    use HandleCachedRaidCritterHealth, ResponseBuilder;

    private int $raidBossCurrentHealth;

    /**
     * @param BuildMonster $buildMonster
     * @param CharacterCacheData $characterCacheData
     * @param MonsterPlayerFight $monsterPlayerFight
     * @param BuildMonsterCacheService $buildMonsterCacheService
     * @param BattleEventHandler $battleEventHandler
     * @param ChanceCalculator $chanceCalculator
     * @param RandomNumberGenerator $randomNumberGenerator
     */
    public function __construct(
        private readonly BuildMonster $buildMonster,
        private readonly CharacterCacheData $characterCacheData,
        private readonly MonsterPlayerFight $monsterPlayerFight,
        private readonly BuildMonsterCacheService $buildMonsterCacheService,
        private readonly BattleEventHandler $battleEventHandler,
        private readonly ChanceCalculator $chanceCalculator,
        private readonly RandomNumberGenerator $randomNumberGenerator,
    ) {}

    /**
     * Set up the raid boss battle's current fight state, initializing its health when not already set up.
     *
     * @param Character $character
     * @param RaidBoss $raidBoss
     * @return array
     */
    public function setUpRaidBossBattle(Character $character, RaidBoss $raidBoss): array
    {

        try {
            $serverMonster = $this->buildServerMonster($character, $raidBoss->raid_boss_id);
        } catch (Exception $e) {
            return $this->errorResult($e->getMessage());
        }

        if (! $this->isRaidBossSetup($raidBoss)) {
            $monsterHealth = $serverMonster->getHealth();

            $raidBoss->update([
                'boss_max_hp' => $monsterHealth,
                'boss_current_hp' => $monsterHealth,
                'raid_boss_deatils' => $serverMonster->getMonster(),
            ]);

            $raidBoss = $raidBoss->refresh();
        }

        $characterHealth = $character->getInformation()->buildHealth();

        $raidBossParticipation = RaidBossParticipation::where('character_id', $character->id)
            ->where('raid_id', $raidBoss->raid_id)
            ->where('raid_boss_id', $raidBoss->id)
            ->first();

        $elementData = $serverMonster->getElementData();

        return $this->successResult([
            'character_max_health' => $characterHealth,
            'character_current_health' => $characterHealth,
            'monster_max_health' => $raidBoss->boss_max_hp,
            'monster_current_health' => $raidBoss->boss_current_hp,
            'attacks_left' => ! is_null($raidBossParticipation) ? $raidBossParticipation->attacks_left : 5,
            'damage_dealt' => ! is_null($raidBossParticipation) ? $raidBossParticipation->damage_dealt : 0,
            'is_raid_boss' => true,
            'elemental_atonemnt' => $elementData,
            'highest_element' => $serverMonster->getHighestElementName($elementData, $serverMonster->getHighestElementDamage($elementData)),
        ]);
    }

    /**
     * Set the current raid boss health this service instance will fight against.
     *
     * @param int $raidBossCurrentHealth
     * @return RaidBattleService
     */
    public function setRaidBossHealth(int $raidBossCurrentHealth): RaidBattleService
    {
        $this->raidBossCurrentHealth = $raidBossCurrentHealth;

        return $this;
    }

    /**
     * Set up a new raid critter Monster fight and return its fight state.
     *
     * @param Character $character
     * @param Monster $monster
     * @return array
     */
    public function setUpRaidCritterMonster(Character $character, Monster $monster): array
    {
        try {
            $serverMonster = $this->buildServerMonster($character, $monster->id);
        } catch (Exception $e) {
            return $this->errorResult($e->getMessage());
        }

        $characterHealth = $character->getInformation()->buildHealth();

        $monsterHealth = $serverMonster->getHealth();

        $elementData = $serverMonster->getElementData();

        return $this->successResult([
            'character_max_health' => $characterHealth,
            'character_current_health' => $characterHealth,
            'monster_max_health' => $monsterHealth,
            'monster_current_health' => $monsterHealth,
            'attacks_left' => 0,
            'is_raid_boss' => false,
            'elemental_atonemnt' => $elementData,
            'highest_element' => $serverMonster->getHighestElementName($elementData, $serverMonster->getHighestElementDamage($elementData)),
        ]);
    }

    /**
     * Resolve one Character attack against the raid boss or raid critter and return the updated fight state.
     *
     * @param Character $character
     * @param int $monsterId
     * @param string $attackType
     * @param bool $isRaidBoss
     * @return array
     */
    public function fightRaidMonster(Character $character, int $monsterId, string $attackType, bool $isRaidBoss = false): array
    {

        try {
            $serverMonster = $this->buildServerMonster($character, $monsterId);
        } catch (Exception $e) {
            return $this->errorResult($e->getMessage());
        }

        if (! $isRaidBoss && $this->hasCachedHealth($character->id, $monsterId)) {
            $serverMonster->setHealth($this->getCachedHealth($character->id, $monsterId));
        }

        if ($isRaidBoss) {
            $serverMonster->setHealth($this->raidBossCurrentHealth);
        }

        $monster = $serverMonster->getMonster();

        $fightData = $this->getFightData($character, $serverMonster, $monsterId, $monster, $attackType, $isRaidBoss);

        $messages = $this->monsterPlayerFight->getBattleMessages();

        if (! $this->hasCachedHealth($character->id, $monsterId)) {
            $preAttackResult = $this->handlePreAttack($character, $fightData['health'], $messages, $monsterId, $isRaidBoss);

            if (! empty($preAttackResult)) {

                return $this->successResult($preAttackResult);
            }
        }

        $this->monsterPlayerFight->processAttack($fightData, true);

        $resultData = $this->buildBaseResultData();

        if ($this->monsterPlayerFight->getCharacterHealth() <= 0) {
            return $this->handleCharacterDeath(
                $character,
                $serverMonster,
                $fightData,
            );
        }

        if ($this->monsterPlayerFight->getMonsterHealth() <= 0) {
            return $this->handleMonsterDeath($character, $serverMonster);
        }

        $this->setCachedHealth($serverMonster, $fightData, $character->id, $monsterId, $resultData['monster_current_health']);

        $this->handleRaidBossHealth($character, $monsterId, $isRaidBoss);

        return $this->successResult($resultData);
    }

    /**
     * Handle the Character's death mid-fight: sync the raid boss health and mark the Character defeated.
     *
     * @param Character $character
     * @param ServerMonster $serverMonster
     * @param array $fightData
     * @return array
     */
    private function handleCharacterDeath(Character $character, ServerMonster $serverMonster, array $fightData): array
    {
        $resultData = $this->buildBaseResultData();

        $monsterId = $serverMonster->getId();

        $isRaidBoss = $serverMonster->isRaidBossMonster();

        $this->handleRaidBossHealth($character, $monsterId, $isRaidBoss);

        $this->battleEventHandler->processDeadCharacter($character);

        $resultData['character_current_health'] = 0;

        $this->setCachedHealth($serverMonster, $fightData, $character->id, $monsterId, $resultData['monster_current_health']);

        return $this->successResult($resultData);
    }

    /**
     * Handle the Monster's death: sync the raid boss health, then dispatch the owning reward handler.
     *
     * @param Character $character
     * @param ServerMonster $serverMonster
     * @return array
     */
    private function handleMonsterDeath(Character $character, ServerMonster $serverMonster): array
    {
        $resultData = $this->buildBaseResultData();

        $monsterId = $serverMonster->getId();

        $isRaidBoss = $serverMonster->isRaidBossMonster();

        $this->handleRaidBossHealth($character, $monsterId, $isRaidBoss);

        $raidBoss = $this->findCurrentRaidBoss($character, $monsterId);
        $raid = is_null($raidBoss) ? null : $raidBoss->raid;

        $resultData['monster_current_health'] = 0;

        $this->deleteMonsterCacheHealth($character->id, $monsterId);

        if (! is_null($raid)) {
            RaidBossRewardHandler::dispatch($character->id, $monsterId, $raid->id);

            return $this->successResult($resultData);
        }

        BattleAttackHandler::dispatch($character->id, $monsterId)->onQueue('battle_reward_processing')->onConnection('battle_reward_processing');

        return $this->successResult($resultData);
    }

    /**
     * Build the base fight result data from the current MonsterPlayerFight state.
     *
     * @return array
     */
    private function buildBaseResultData(): array
    {
        return [
            'character_current_health' => $this->monsterPlayerFight->getCharacterHealth(),
            'monster_current_health' => max($this->monsterPlayerFight->getMonsterHealth(), 0),
            'messages' => $this->monsterPlayerFight->getBattleMessages(),
        ];
    }

    /**
     * Get the fight data for the raid boss or raid critter, refreshing the raid boss's persisted state when needed.
     *
     * @param Character $character
     * @param ServerMonster $serverMonster
     * @param int $monsterId
     * @param array $monster
     * @param string $attackType
     * @param bool $isRaidBoss
     * @return array
     */
    private function getFightData(Character $character, ServerMonster $serverMonster, int $monsterId, array $monster, string $attackType, bool $isRaidBoss): array
    {
        if (! $isRaidBoss && $this->hasCachedHealth($character->id, $monsterId)) {
            $fightData = $this->getCachedFightData($character->id, $monsterId);
            $fightData['health']['current_monster_health'] = $serverMonster->getHealth();

            $this->monsterPlayerFight->setUpRaidFight($character, $monster, $attackType);
        } else {
            $fightData = $this->monsterPlayerFight->setUpRaidFight($character, $monster, $attackType)->fightSetUp();
        }

        if ($isRaidBoss) {
            $raidBoss = $this->findCurrentRaidBoss($character, $monsterId);

            $fightData['monster'] = (new ServerMonster($this->chanceCalculator, $this->randomNumberGenerator))
                ->setMonster($raidBoss->raid_boss_deatils)
                ->setHealth($raidBoss->boss_current_hp);
            $fightData['health']['current_monster_health'] = $raidBoss->boss_current_hp;
        }

        return $fightData;
    }

    /**
     * Process the pre-attack outcome for an ambush, handling an already-decided Character or Monster death.
     *
     * @param Character $character
     * @param array $health
     * @param array $messages
     * @param int $monsterId
     * @param bool $isRaidBoss
     * @return array
     */
    private function handlePreAttack(Character $character, array $health, array $messages, int $monsterId, bool $isRaidBoss = false): array
    {
        if ($health['current_character_health'] <= 0) {
            $health['current_character_health'] = 0;

            $messages[] = [
                'message' => 'The enemies ambush has slaughtered you!',
                'type' => 'enemy-action',
            ];

            $this->handleRaidBossHealth($character, $monsterId, $isRaidBoss, $health);

            $this->battleEventHandler->processDeadCharacter($character);

            return $this->successResult([
                'character_current_health' => 0,
                'monster_current_health' => $health['current_monster_health'],
                'messages' => $messages,
            ]);
        }

        if ($health['current_monster_health'] <= 0) {
            $health['current_monster_health'] = 0;

            $messages[] = [
                'message' => 'Your ambush has slaughtered the enemy!',
                'type' => 'enemy-action',
            ];

            $this->handleRaidBossHealth($character, $monsterId, $isRaidBoss, $health);

            $raidBoss = $this->findCurrentRaidBoss($character, $monsterId);
            $raid = is_null($raidBoss) ? null : $raidBoss->raid;

            if (is_null($raid)) {
                BattleAttackHandler::dispatch($character->id, $this->monsterPlayerFight->getMonster()['id'])
                    ->onQueue('battle_reward_processing')
                    ->onConnection('battle_reward_processing');

                return $this->successResult([
                    'character_current_health' => $health['current_character_health'],
                    'monster_current_health' => 0,
                    'messages' => $messages,
                ]);
            }

            RaidBossRewardHandler::dispatch($character->id, $monsterId, $raid->id)
                ->onQueue('battle_reward_processing')
                ->onConnection('battle_reward_processing')
                ->delay(now()->addSeconds(2));

            return $this->successResult([
                'character_current_health' => $health['current_character_health'],
                'monster_current_health' => 0,
                'messages' => $messages,
            ]);
        }

        return [];
    }

    /**
     * Update the raid boss's persisted health to the lower of its current or newly calculated health.
     *
     * @param RaidBoss $raidBoss
     * @param int $newHealth
     * @return void
     */
    private function updateRaidBossHealth(RaidBoss $raidBoss, int $newHealth): void
    {
        $raidBoss->update([
            // always get the latest and greates new health when updating.
            'boss_current_hp' => $raidBoss->refresh()->boss_current_hp < $newHealth ? $raidBoss->refresh()->boss_current_hp : $newHealth,
        ]);

        $raidBoss = $raidBoss->refresh();

        event(new UpdateRaidBossHealth($raidBoss->id, $raidBoss->boss_current_hp));
    }

    /**
     * Sync the current raid boss health and participation record when this fight is against a raid boss.
     *
     * @param Character $character
     * @param int $monsterId
     * @param bool $shouldUpdateHealth
     * @param array $health
     * @return void
     */
    private function handleRaidBossHealth(Character $character, int $monsterId, bool $shouldUpdateHealth, array $health = []): void
    {
        if (! $shouldUpdateHealth) {
            return;
        }

        $raidBoss = $this->findCurrentRaidBoss($character, $monsterId);

        if (is_null($raidBoss)) {
            return;
        }

        $oldHealth = $raidBoss->boss_current_hp;

        $currentHealth = max(empty($health) ? $this->monsterPlayerFight->getMonsterHealth() : $health['current_monster_health'], 0);

        $this->updateRaidBossHealth($raidBoss, $currentHealth);

        $this->updateRaidParticipation($character, $raidBoss, $oldHealth);
    }

    /**
     * Update or create the Character's raid boss participation record for the damage just dealt.
     *
     * @param Character $character
     * @param RaidBoss $raidBoss
     * @param int $oldHealth
     * @return void
     */
    private function updateRaidParticipation(Character $character, RaidBoss $raidBoss, int $oldHealth): void
    {
        $raidBossParticipation = RaidBossParticipation::where('character_id', $character->id)
            ->where('raid_id', $raidBoss->raid_id)
            ->where('raid_boss_id', $raidBoss->id)
            ->first();

        $newHealth = $raidBoss->refresh()->boss_current_hp;
        $damageDealt = ($oldHealth - ($newHealth <= 0 ? 0 : $newHealth));
        $killedRaidBoss = $damageDealt >= $oldHealth;

        if (! is_null($raidBossParticipation)) {
            $attacksLeft = $raidBossParticipation->attacks_left - 1;

            $newDamageAmount = $raidBossParticipation->damage_dealt + ($damageDealt >= $oldHealth ? $oldHealth : $damageDealt);

            $raidBossParticipation->update([
                'attacks_left' => $attacksLeft <= 0 ? 0 : $attacksLeft,
                'damage_dealt' => $newDamageAmount,
                'killed_boss' => $killedRaidBoss,
            ]);

            $raidBossParticipation = $raidBossParticipation->refresh();

            if (! is_null($raidBossParticipation)) {
                event(new UpdateRaidAttacksLeft($character->user_id, ($attacksLeft <= 0 ? 0 : $attacksLeft), $raidBossParticipation->damage_dealt, $raidBoss->raid_boss_id));
            }

            if ($killedRaidBoss) {
                event(new UpdateRaidAttacksLeft($character->user_id, 0, $raidBossParticipation->damage_dealt, $raidBoss->raid_boss_id));
            }

            return;
        }

        $raidBossParticipation = RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $raidBoss->raid->id,
            'raid_boss_id' => $raidBoss->id,
            'attacks_left' => 4,
            'damage_dealt' => $damageDealt,
            'killed_boss' => $killedRaidBoss,
        ]);

        if ($killedRaidBoss) {
            event(new UpdateRaidAttacksLeft($character->user_id, 0, $raidBossParticipation->damage_dealt, $raidBoss->raid_boss_id));

            return;
        }

        event(new UpdateRaidAttacksLeft($character->user_id, 4, $raidBossParticipation->damage_dealt, $raidBoss->raid_boss_id));
    }

    /**
     * Build the server-authoritative Monster for the given raid Monster id from the cached raid Monster list.
     *
     * @param Character $character
     * @param int $monsterId
     * @return ServerMonster
     */
    private function buildServerMonster(Character $character, int $monsterId): ServerMonster
    {
        $characterStatReductionAffixes = $this->characterCacheData->getCachedCharacterData($character, 'stat_affixes');
        $skillReduction = $this->characterCacheData->getCachedCharacterData($character, 'skill_reduction');
        $resistanceReduction = $this->characterCacheData->getCachedCharacterData($character, 'resistance_reduction');

        $cache = Cache::get('raid-monsters');

        if (is_null($cache)) {
            $this->buildMonsterCacheService->buildRaidCache();

            $cache = Cache::get('raid-monsters');
        }

        $raidMonsters = $cache[$character->map->gameMap->name];
        $monster = [];

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
     * Determine whether the raid boss's health has already been initialized.
     *
     * @param RaidBoss $raidBoss
     * @return bool
     */
    private function isRaidBossSetup(RaidBoss $raidBoss): bool
    {

        return ! is_null($raidBoss->boss_max_hp) && ! is_null($raidBoss->boss_current_hp);
    }

    /**
     * Resolve the active raid boss at the Character's current location for the given Monster id, or null when none is active.
     *
     * @param Character $character
     * @param int $monsterId
     * @return ?RaidBoss
     */
    private function findCurrentRaidBoss(Character $character, int $monsterId): ?RaidBoss
    {
        $location = Location::where('game_map_id', $character->map->game_map_id)
            ->where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->first();

        if (is_null($location) || is_null($location->raid_id)) {
            return null;
        }

        return RaidBoss::where('raid_id', $location->raid_id)
            ->where('raid_boss_id', $monsterId)
            ->first();
    }
}
