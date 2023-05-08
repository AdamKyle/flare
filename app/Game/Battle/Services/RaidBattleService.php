<?php

namespace  App\Game\Battle\Services;

use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Models\RaidBoss;
use App\Flare\ServerFight\Monster\BuildMonster;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Game\Core\Traits\ResponseBuilder;

class RaidBattleService {

    use ResponseBuilder;

    private BuildMonster $buildMonster;

    private CharacterCacheData $characterCacheData;

    private MonsterPlayerFight $monsterPlayerFight;

    public function __construct(BuildMonster $buildMonster, CharacterCacheData $characterCacheData, MonsterPlayerFight $monsterPlayerFight) {
        $this->buildMonster       = $buildMonster;
        $this->characterCacheData = $characterCacheData;
        $this->monsterPlayerFight = $monsterPlayerFight;
    }

    public function setUpRaidBattle(Character $character, RaidBoss $raidBoss): array {
        $characterStatReductionAffixes = $this->characterCacheData->getCachedCharacterData($character, 'stat_affixes');
        $skillReduction                = $this->characterCacheData->getCachedCharacterData($character, 'skill_reduction');
        $resistanceReduction           = $this->characterCacheData->getCachedCharacterData($character, 'resistance_reduction');
        $monster                       = Monster::find($raidBoss->raid_boss_id);

        $serverMonster = $this->buildMonster->buildMonster($monster, $characterStatReductionAffixes, $skillReduction, $resistanceReduction);

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

    public function fightMonster(Character $character, RaidBoss $raidBoss): array {
        return $this->successResult();
    }

    private function isRaidBossSetup(RaidBoss $raidBoss): bool {

        return is_null($raidBoss->boss_max_hp) && is_null($raidBoss->boss_current_hp);
    }
}
