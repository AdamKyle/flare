<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Game\Battle\ServerFight\Monster\BuildMonster;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Mockery;

class BuildMonsterFactory
{
    public function buildBuildMonster(?ChanceCalculator $chanceCalculator = null, ?RandomNumberGenerator $randomNumberGenerator = null): BuildMonster
    {
        $randomNumberGenerator ??= Mockery::mock(RandomNumberGenerator::class);

        return new BuildMonster(
            $this->buildServerMonster(),
            $chanceCalculator ?? new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
        );
    }

    public function buildServerMonster(): ServerMonster
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator);
    }
}
