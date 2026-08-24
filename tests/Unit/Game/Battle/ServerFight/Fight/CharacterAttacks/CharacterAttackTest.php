<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks;

use App\Game\Battle\ServerFight\Fight\CharacterAttacks\CharacterAttack;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\AttackAndCast;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\CastAndAttack;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\CastType;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\Defend;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\WeaponType;
use App\Game\Core\Combat\Values\AttackType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Battle\ServerFight\CharacterAttackFactory;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterAttackTest extends TestCase
{
    use RefreshDatabase;

    private CharacterAttackFactory $characterAttackFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterAttackFactory = new CharacterAttackFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->characterAttackFactory);
    }

    public function test_attack_delegates_to_weapon_type_and_tracks_the_active_type(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = $this->characterAttackFactory->buildMonster();

        $weaponType = Mockery::mock(WeaponType::class);
        $weaponType->shouldReceive('setIsRaidBoss')->once()->with(false);
        $weaponType->shouldReceive('setCharacterHealth')->once()->with(1000);
        $weaponType->shouldReceive('setMonsterHealth')->once()->with(2000);
        $weaponType->shouldReceive('setCharacterAttackData')->once()->with($character, false, AttackType::ATTACK->value);
        $weaponType->shouldReceive('setAllowEntrancing')->once()->with(true);
        $weaponType->shouldReceive('doWeaponAttack')->once()->with($character, $monster);
        $weaponType->shouldReceive('getMessages')->once()->andReturn([['message' => 'weapon', 'type' => 'regular']]);
        $weaponType->shouldReceive('getCharacterHealth')->once()->andReturn(900);
        $weaponType->shouldReceive('getMonsterHealth')->once()->andReturn(1800);
        $weaponType->shouldReceive('resetMessages')->once();

        $characterAttack = new CharacterAttack(
            $weaponType,
            Mockery::mock(CastType::class),
            Mockery::mock(AttackAndCast::class),
            Mockery::mock(CastAndAttack::class),
            Mockery::mock(Defend::class),
        );

        $result = $characterAttack->attack($character, $monster, false, 1000, 2000);

        $this->assertSame($characterAttack, $result);
        $this->assertSame([['message' => 'weapon', 'type' => 'regular']], $characterAttack->getMessages());
        $this->assertSame(900, $characterAttack->getCharacterHealth());
        $this->assertSame(1800, $characterAttack->getMonsterHealth());

        $characterAttack->resetMessages();
    }

    public function test_cast_delegates_to_cast_type(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = $this->characterAttackFactory->buildMonster(true);

        $castType = Mockery::mock(CastType::class);
        $castType->shouldReceive('setIsRaidBoss')->once()->with(true);
        $castType->shouldReceive('setCharacterHealth')->once()->with(1000);
        $castType->shouldReceive('setMonsterHealth')->once()->with(2000);
        $castType->shouldReceive('setCharacterAttackData')->once()->with($character, true, AttackType::CAST->value);
        $castType->shouldReceive('setAllowEntrancing')->once()->with(true);
        $castType->shouldReceive('castAttack')->once()->with($character, $monster);
        $castType->shouldReceive('getCharacterHealth')->once()->andReturn(950);

        $characterAttack = new CharacterAttack(
            Mockery::mock(WeaponType::class),
            $castType,
            Mockery::mock(AttackAndCast::class),
            Mockery::mock(CastAndAttack::class),
            Mockery::mock(Defend::class),
        );

        $characterAttack->cast($character, $monster, true, 1000, 2000);

        $this->assertSame(950, $characterAttack->getCharacterHealth());
    }

    public function test_attack_and_cast_delegates_to_attack_and_cast(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = $this->characterAttackFactory->buildMonster();

        $attackAndCast = Mockery::mock(AttackAndCast::class);
        $attackAndCast->shouldReceive('setIsRaidBoss')->once()->with(false);
        $attackAndCast->shouldReceive('setCharacterHealth')->once()->with(1000);
        $attackAndCast->shouldReceive('setMonsterHealth')->once()->with(2000);
        $attackAndCast->shouldReceive('setCharacterAttackData')->once()->with($character, false, AttackType::ATTACK_AND_CAST->value);
        $attackAndCast->shouldReceive('handleAttack')->once()->with($character, $monster);
        $attackAndCast->shouldReceive('getMonsterHealth')->once()->andReturn(1500);

        $characterAttack = new CharacterAttack(
            Mockery::mock(WeaponType::class),
            Mockery::mock(CastType::class),
            $attackAndCast,
            Mockery::mock(CastAndAttack::class),
            Mockery::mock(Defend::class),
        );

        $characterAttack->attackAndCast($character, $monster, false, 1000, 2000);

        $this->assertSame(1500, $characterAttack->getMonsterHealth());
    }

    public function test_cast_and_attack_delegates_to_cast_and_attack(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = $this->characterAttackFactory->buildMonster();

        $castAndAttack = Mockery::mock(CastAndAttack::class);
        $castAndAttack->shouldReceive('setIsRaidBoss')->once()->with(false);
        $castAndAttack->shouldReceive('setCharacterHealth')->once()->with(1000);
        $castAndAttack->shouldReceive('setMonsterHealth')->once()->with(2000);
        $castAndAttack->shouldReceive('setCharacterCastAndAttackkData')->once()->with($character, false);
        $castAndAttack->shouldReceive('handleAttack')->once()->with($character, $monster);
        $castAndAttack->shouldReceive('getMonsterHealth')->once()->andReturn(1600);

        $characterAttack = new CharacterAttack(
            Mockery::mock(WeaponType::class),
            Mockery::mock(CastType::class),
            Mockery::mock(AttackAndCast::class),
            $castAndAttack,
            Mockery::mock(Defend::class),
        );

        $characterAttack->castAndAttack($character, $monster, false, 1000, 2000);

        $this->assertSame(1600, $characterAttack->getMonsterHealth());
    }

    public function test_defend_delegates_to_defend(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = $this->characterAttackFactory->buildMonster();

        $defend = Mockery::mock(Defend::class);
        $defend->shouldReceive('setIsRaidBoss')->once()->with(false);
        $defend->shouldReceive('setCharacterHealth')->once()->with(1000);
        $defend->shouldReceive('setMonsterHealth')->once()->with(2000);
        $defend->shouldReceive('setCharacterAttackData')->once()->with($character, false);
        $defend->shouldReceive('defend')->once()->with($character, $monster);
        $defend->shouldReceive('getCharacterHealth')->once()->andReturn(1000);

        $characterAttack = new CharacterAttack(
            Mockery::mock(WeaponType::class),
            Mockery::mock(CastType::class),
            Mockery::mock(AttackAndCast::class),
            Mockery::mock(CastAndAttack::class),
            $defend,
        );

        $characterAttack->defend($character, $monster, false, 1000, 2000);

        $this->assertSame(1000, $characterAttack->getCharacterHealth());
    }
}
