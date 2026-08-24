<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\Types;

use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\AttackAndCast;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\CastType;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\WeaponType;
use App\Game\Battle\ServerFight\Fight\Entrance;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Combat\Values\AttackType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Battle\ServerFight\CharacterCacheDataFactory;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class AttackAndCastTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_attack_runs_weapon_then_cast_and_stops_after_weapon_if_character_dies(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $monsterRandomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $monster = (new ServerMonster(new ChanceCalculator($monsterRandomNumberGenerator), $monsterRandomNumberGenerator))->setMonster(['is_raid_boss' => false]);

        $weaponType = Mockery::mock(WeaponType::class);
        $weaponType->shouldReceive('setMonsterHealth')->once();
        $weaponType->shouldReceive('setCharacterHealth')->once();
        $weaponType->shouldReceive('setCharacterAttackData')->once()->with($character, false, AttackType::ATTACK_AND_CAST->value);
        $weaponType->shouldReceive('doWeaponAttack')->once();
        $weaponType->shouldReceive('getMessages')->once()->andReturn([]);
        $weaponType->shouldReceive('getCharacterHealth')->once()->andReturn(0);
        $weaponType->shouldReceive('getMonsterHealth')->once()->andReturn(500);
        $weaponType->shouldReceive('resetMessages')->once();

        $entrance = Mockery::mock(Entrance::class);
        $entrance->shouldReceive('playerEntrance')->once();
        $entrance->shouldReceive('getMessages')->once()->andReturn([]);
        $entrance->shouldReceive('isEnemyEntranced')->once()->andReturn(false);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        $attackAndCast = new AttackAndCast(
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
            $entrance,
            $weaponType,
            Mockery::mock(CastType::class),
        );
        $attackAndCast->setCharacterHealth(1000);
        $attackAndCast->setMonsterHealth(1000);
        $attackAndCast->setCharacterAttackData($character, false);

        $result = $attackAndCast->handleAttack($character, $monster);

        $this->assertSame($attackAndCast, $result);
        $this->assertSame(0, $attackAndCast->getCharacterHealth());
        $this->assertSame(500, $attackAndCast->getMonsterHealth());
    }

    public function test_handle_attack_runs_both_weapon_and_cast_when_the_character_survives(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $monsterRandomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $monster = (new ServerMonster(new ChanceCalculator($monsterRandomNumberGenerator), $monsterRandomNumberGenerator))->setMonster(['is_raid_boss' => false]);

        $weaponType = Mockery::mock(WeaponType::class);
        $weaponType->shouldReceive('setMonsterHealth')->once();
        $weaponType->shouldReceive('setCharacterHealth')->once();
        $weaponType->shouldReceive('setCharacterAttackData')->once();
        $weaponType->shouldReceive('doWeaponAttack')->once();
        $weaponType->shouldReceive('getMessages')->once()->andReturn([]);
        $weaponType->shouldReceive('getCharacterHealth')->once()->andReturn(900);
        $weaponType->shouldReceive('getMonsterHealth')->once()->andReturn(500);
        $weaponType->shouldReceive('resetMessages')->once();

        $castType = Mockery::mock(CastType::class);
        $castType->shouldReceive('setMonsterHealth')->once();
        $castType->shouldReceive('setCharacterHealth')->once();
        $castType->shouldReceive('setCharacterAttackAndCast')->once()->with($character, false);
        $castType->shouldReceive('doNotAllowSecondaryAttacks')->once();
        $castType->shouldReceive('castAttack')->once();
        $castType->shouldReceive('getMessages')->once()->andReturn([]);
        $castType->shouldReceive('getCharacterHealth')->once()->andReturn(800);
        $castType->shouldReceive('getMonsterHealth')->once()->andReturn(200);
        $castType->shouldReceive('resetMessages')->once();

        $entrance = Mockery::mock(Entrance::class);
        $entrance->shouldReceive('playerEntrance')->twice();
        $entrance->shouldReceive('getMessages')->twice()->andReturn([]);
        $entrance->shouldReceive('isEnemyEntranced')->twice()->andReturn(false);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        $attackAndCast = new AttackAndCast(
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
            $entrance,
            $weaponType,
            $castType,
        );
        $attackAndCast->setCharacterHealth(1000);
        $attackAndCast->setMonsterHealth(1000);
        $attackAndCast->setCharacterAttackData($character, false);

        $attackAndCast->handleAttack($character, $monster);

        $this->assertSame(800, $attackAndCast->getCharacterHealth());
        $this->assertSame(200, $attackAndCast->getMonsterHealth());
    }

    public function test_reset_messages_clears_both_own_and_entrance_messages(): void
    {
        $entrance = Mockery::mock(Entrance::class);
        $entrance->shouldReceive('clearMessages')->once();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        $attackAndCast = new AttackAndCast(
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
            $entrance,
            Mockery::mock(WeaponType::class),
            Mockery::mock(CastType::class),
        );

        $attackAndCast->resetMessages();

        $this->assertEmpty($attackAndCast->getMessages());
    }
}
