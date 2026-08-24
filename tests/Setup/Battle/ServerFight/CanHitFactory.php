<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\Fight\CanHit;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Support\Facades\Cache;
use Mockery;

class CanHitFactory
{
    public function buildCanHit(?ChanceCalculator $chanceCalculator = null): CanHit
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new CanHit(
            (new CharacterCacheDataFactory())->build(),
            $chanceCalculator ?? new ChanceCalculator($randomNumberGenerator),
        );
    }

    public function seedCharacterSheet(Character $character, array $overrides = []): void
    {
        Cache::put('character-sheet-'.$character->id, array_merge([
            'level' => $character->level,
            'to_hit_stat' => 'str',
            'str' => 50,
            'str_modded' => 50,
            'agi' => 50,
            'agi_modded' => 50,
            'skills' => [
                'accuracy' => 0,
                'casting_accuracy' => 0,
                'dodge' => 0,
                'criticality' => 0,
            ],
            'extra_action_chance' => [
                'has_item' => false,
                'chance' => 0,
            ],
        ], $overrides));
    }

    public function buildMonster(array $monster): ServerMonster
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster(array_merge([
            'agi' => 10,
            'dodge' => 0,
            'to_hit_base' => 10,
            'accuracy' => 0,
            'casting_accuracy' => 0,
            'is_raid_boss' => false,
        ], $monster));
    }
}
