<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\CastAndAttack;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\CastType;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\WeaponType;
use App\Game\Battle\ServerFight\Fight\Entrance;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Mockery;
use Tests\Setup\Character\CharacterFactory;

class CastAndAttackFactory
{
    public function buildCastAndAttack(Entrance $entrance, WeaponType $weaponType, CastType $castType): CastAndAttack
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new CastAndAttack(
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
            $entrance,
            $weaponType,
            $castType,
        );
    }

    public function buildCharacter(): Character
    {
        return (new CharacterFactory())->createBaseCharacter()->getCharacter();
    }

    public function buildMonster(): ServerMonster
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster(['is_raid_boss' => false]);
    }

    public function noEntrance(int $times = 1): Entrance
    {
        $entrance = Mockery::mock(Entrance::class);
        $entrance->shouldReceive('playerEntrance')->times($times);
        $entrance->shouldReceive('getMessages')->times($times)->andReturn([]);
        $entrance->shouldReceive('isEnemyEntranced')->times($times)->andReturn(false);

        return $entrance;
    }
}
