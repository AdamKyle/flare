<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Mockery;

class AttackFactory
{
    public function buildMonster(array $monster = []): ServerMonster
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setHealth(1000)->setMonster(array_merge([
            'name' => 'Test Monster',
            'is_raid_boss' => false,
        ], $monster));
    }
}
