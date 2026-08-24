<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\Fight\Voidance;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameMap;

class VoidanceFactory
{
    use CreateGameMap;

    public function buildVoidance(): Voidance
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new Voidance(new ChanceCalculator($randomNumberGenerator));
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
            'devouring_darkness' => 0.0,
            'devouring_darkness_res' => 0.0,
            'devouring_light_res' => 0.0,
        ], $overrides));
    }

    public function buildMonster(array $monster = []): ServerMonster
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster(array_merge([
            'name' => 'Test Monster',
            'devouring_darkness_chance' => 0.0,
            'devouring_light_chance' => 0.0,
        ], $monster));
    }
}
