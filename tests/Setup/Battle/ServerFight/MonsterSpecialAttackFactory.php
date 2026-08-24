<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Game\Battle\ServerFight\Monster\MonsterSpecialAttack;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Mockery;

class MonsterSpecialAttackFactory
{
    public function buildMonsterSpecialAttack(): MonsterSpecialAttack
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new MonsterSpecialAttack(
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
        );
    }
}
