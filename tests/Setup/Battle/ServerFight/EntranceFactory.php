<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\Fight\Entrance;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Mockery;
use Tests\Setup\Character\CharacterFactory;

class EntranceFactory
{
    public function build(?ChanceCalculator $chanceCalculator = null): Entrance
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new Entrance(
            (new CharacterCacheDataFactory())->build(),
            $chanceCalculator ?? new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
        );
    }

    public function buildMonster(array $monster = []): ServerMonster
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster(array_merge([
            'name' => 'Test Monster',
            'affix_resistance' => 0.0,
            'entrance_chance' => 0.0,
        ], $monster));
    }

    public function buildCharacter(array $classOptions = []): Character
    {
        return (new CharacterFactory())->createBaseCharacter([], $classOptions)->getCharacter();
    }
}
