<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\Fight\Affixes;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Mockery;
use Tests\Setup\Character\CharacterFactory;

class AffixesFactory
{
    public function buildAffixes(?ChanceCalculator $chanceCalculator = null): Affixes
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new Affixes(
            (new CharacterCacheDataFactory())->build(),
            $chanceCalculator ?? new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
        );
    }

    public function buildCharacter(array $classOptions = []): Character
    {
        return (new CharacterFactory())->createBaseCharacter([], $classOptions)->getCharacter();
    }
}
