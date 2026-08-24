<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Game\Battle\ServerFight\Fight\ElementalAttack;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Combat\Values\ElementAttackData;
use Mockery;

class ElementalAttackFactory
{
    public function buildElementalAttack(): ElementalAttack
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new ElementalAttack(
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
            new ElementAttackData(),
        );
    }
}
