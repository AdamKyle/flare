<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Game\Battle\ServerFight\Fight\CanHit;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Counter;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\PlayerHealing;
use App\Game\Battle\ServerFight\Fight\ElementalAttack;
use App\Game\Battle\ServerFight\Fight\Entrance;
use App\Game\Battle\ServerFight\Fight\MonsterAttack;
use App\Game\Battle\ServerFight\Monster\MonsterSpecialAttack;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Mockery;

class MonsterAttackFactory
{
    public function buildMonsterAttack(
        ?PlayerHealing $playerHealing = null,
        ?Entrance $entrance = null,
        ?CanHit $canHit = null,
        ?ElementalAttack $elementalAttack = null,
        ?MonsterSpecialAttack $monsterSpecialAttack = null,
        ?Counter $counter = null,
    ): MonsterAttack {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new MonsterAttack(
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
            $playerHealing ?? Mockery::mock(PlayerHealing::class),
            $entrance ?? Mockery::mock(Entrance::class),
            $canHit ?? Mockery::mock(CanHit::class),
            $elementalAttack ?? Mockery::mock(ElementalAttack::class),
            $monsterSpecialAttack ?? Mockery::mock(MonsterSpecialAttack::class),
            $counter ?? Mockery::mock(Counter::class),
        );
    }
}
