<?php

namespace  App\Game\Battle\Services;

use App\Flare\Models\Monster;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\Character;
use App\Game\Core\Traits\ResponseBuilder;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Flare\ServerFight\Monster\BuildMonster;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Services\BuildMonsterCacheService;
use Exception;
use Facade\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Support\Facades\Cache;

class RaidBattleService {

    use ResponseBuilder;

    private BuildMonster $buildMonster;

    private CharacterCacheData $characterCacheData;

    private MonsterPlayerFight $monsterPlayerFight;

    private BuildMonsterCacheService $buildMonsterCacheService;


    public function __construct(BuildMonster $buildMonster, 
                                CharacterCacheData $characterCacheData, 
                                MonsterPlayerFight $monsterPlayerFight,
                                BuildMonsterCacheService $buildMonsterCacheService
    ) {
        $this->buildMonster             = $buildMonster;
        $this->characterCacheData       = $characterCacheData;
        $this->monsterPlayerFight       = $monsterPlayerFight;
        $this->buildMonsterCacheService = $buildMonsterCacheService;

    }

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

    public function fightRaidBossMonster(Character $character, RaidBoss $raidBoss): array {
        return $this->successResult();
    }

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

    private function isRaidBossSetup(RaidBoss $raidBoss): bool {

        return is_null($raidBoss->boss_max_hp) && is_null($raidBoss->boss_current_hp);
    }
}
