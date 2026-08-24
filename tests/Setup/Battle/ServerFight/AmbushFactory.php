<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\Fight\Ambush;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameMap;

class AmbushFactory
{
    use CreateGameMap;

    public function buildAmbush(?ChanceCalculator $chanceCalculator = null): Ambush
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new Ambush(
            (new CharacterCacheDataFactory())->build(),
            $chanceCalculator ?? new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
        );
    }

    public function buildCharacter(bool $purgatory = false): Character
    {
        $gameMap = $purgatory
            ? $this->createGameMap(['name' => 'Purgatory', 'path' => 'purgatory', 'default' => false])
            : null;

        return (new CharacterFactory())->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();
    }

    public function seedCharacterSheet(Character $character, array $overrides = []): void
    {
        Cache::put('character-sheet-'.$character->id, array_merge([
            'level' => $character->level,
            'health' => 1000,
            'ambush_resistance_chance' => 0.0,
            'ambush_chance' => 0.0,
            'base_stat' => 10,
            'voided_base_stat' => 5,
        ], $overrides));
    }

    public function buildMonster(array $monster = []): ServerMonster
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->with(20, 20)->andReturn(20);

        return (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setHealth(500)->setMonster(array_merge([
            'name' => 'Test Monster',
            'is_raid_boss' => false,
            'ambush_chance' => 0.0,
            'ambush_resistance_chance' => 0.0,
            'attack_range' => '20-20',
            'increases_damage_by' => null,
        ], $monster));
    }
}
