<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Game\Battle\ServerFight\Fight\Affixes;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Game\Battle\ServerFight\Fight\ElementalAttack;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Combat\Values\ElementAttackData;
use Mockery;

class SecondaryAttacksFactory
{
    public function buildSecondaryAttacks(?Affixes $affixes = null, ?ElementalAttack $elementalAttack = null): SecondaryAttacks
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new SecondaryAttacks(
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
            $affixes ?? $this->buildAffixes(),
            $elementalAttack ?? $this->buildElementalAttack(),
        );
    }

    public function buildAffixes(): Affixes
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new Affixes(
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
        );
    }

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

    public function buildServerMonster(array $monsterAttributes, int $health = 1000): ServerMonster
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))
            ->setHealth($health)
            ->setMonster($monsterAttributes);
    }
}
