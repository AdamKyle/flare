<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Counter;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Character\CharacterFactory;

class CounterFactory
{
    public function buildCounter(?ChanceCalculator $chanceCalculator = null): Counter
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new Counter(
            (new CharacterCacheDataFactory())->build(),
            $chanceCalculator ?? new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
        );
    }

    public function buildCharacter(): Character
    {
        return (new CharacterFactory())->createBaseCharacter()->getCharacter();
    }

    public function buildMonster(array $monster = []): ServerMonster
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->with(20, 20)->andReturn(20);

        return (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster(array_merge([
            'is_raid_boss' => false,
            'counter_chance' => 0.0,
            'counter_resistance_chance' => 0.0,
            'ac' => 10,
        ], $monster));
    }

    public function seedCharacterSheet(Character $character, array $overrides = []): void
    {
        Cache::put('character-sheet-'.$character->id, array_merge([
            'level' => $character->level,
            'ac' => 10,
            'counter_resistance_chance' => 0.0,
            'counter_chance' => 0.0,
        ], $overrides));
    }
}
