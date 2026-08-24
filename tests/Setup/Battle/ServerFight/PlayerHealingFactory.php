<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Game\Battle\ServerFight\Fight\Affixes;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\PlayerHealing;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\CastType;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Mockery;

class PlayerHealingFactory
{
    public function buildPlayerHealing(?Affixes $affixes = null, ?CastType $castType = null): PlayerHealing
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new PlayerHealing(
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
            $affixes ?? $this->buildAffixes(),
            $castType ?? Mockery::mock(CastType::class),
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
}
