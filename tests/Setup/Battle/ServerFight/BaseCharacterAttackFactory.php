<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Mockery;

class BaseCharacterAttackFactory
{
    public function buildMonster(): ServerMonster
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster(['is_raid_boss' => false]);
    }
}
