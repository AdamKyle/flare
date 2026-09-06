<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Game\Battle\ServerFight\Fight\Ambush;
use App\Game\Battle\ServerFight\Fight\Attack;
use App\Game\Battle\ServerFight\Fight\Voidance;
use App\Game\Battle\ServerFight\Monster\BuildMonster;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Battle\ServerFight\MonsterPlayerFight;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Combat\Values\ElementAttackData;
use App\Game\Exploration\Services\DelveMonsterService;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use App\Game\Monsters\Services\MonsterListService;
use Mockery;

class MonsterPlayerFightFactory
{
    public function build(
        ?BuildMonster $buildMonster = null,
        ?CharacterCacheData $characterCacheData = null,
        ?DelveMonsterService $delveMonsterService = null,
        ?Voidance $voidance = null,
        ?Ambush $ambush = null,
        ?Attack $attack = null,
        ?BuildMonsterCacheService $buildMonsterCacheService = null,
        ?ElementAttackData $elementAttackData = null,
        ?MonsterListService $monsterListService = null,
    ): MonsterPlayerFight {
        return new MonsterPlayerFight(
            $buildMonster ?? Mockery::mock(BuildMonster::class),
            $characterCacheData ?? Mockery::mock(CharacterCacheData::class),
            $delveMonsterService ?? Mockery::mock(DelveMonsterService::class),
            $voidance ?? Mockery::mock(Voidance::class),
            $ambush ?? Mockery::mock(Ambush::class),
            $attack ?? Mockery::mock(Attack::class),
            $buildMonsterCacheService ?? Mockery::mock(BuildMonsterCacheService::class),
            $elementAttackData ?? new ElementAttackData(),
            $monsterListService ?? resolve(MonsterListService::class),
        );
    }

    public function buildServerMonster(array $monster = []): ServerMonster
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster(array_merge([
            'id' => 1,
            'name' => 'Test Monster',
        ], $monster))->setHealth(1000);
    }
}
