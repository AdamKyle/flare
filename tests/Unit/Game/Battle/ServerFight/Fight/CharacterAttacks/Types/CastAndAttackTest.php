<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\Types;

use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\CastType;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\WeaponType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Battle\ServerFight\CastAndAttackFactory;
use Tests\TestCase;

class CastAndAttackTest extends TestCase
{
    use RefreshDatabase;

    private CastAndAttackFactory $castAndAttackFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->castAndAttackFactory = new CastAndAttackFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->castAndAttackFactory);
    }

    public function test_handle_attack_stops_after_cast_when_the_character_dies(): void
    {
        $character = $this->castAndAttackFactory->buildCharacter();
        $monster = $this->castAndAttackFactory->buildMonster();

        $castType = Mockery::mock(CastType::class);
        $castType->shouldReceive('setMonsterHealth')->once();
        $castType->shouldReceive('setCharacterHealth')->once();
        $castType->shouldReceive('setCharacterCastAndAttack')->once()->with($character, false);
        $castType->shouldReceive('castAttack')->once();
        $castType->shouldReceive('getMessages')->once()->andReturn([]);
        $castType->shouldReceive('getCharacterHealth')->once()->andReturn(0);
        $castType->shouldReceive('getMonsterHealth')->once()->andReturn(500);
        $castType->shouldReceive('resetMessages')->once();

        $castAndAttack = $this->castAndAttackFactory->buildCastAndAttack($this->castAndAttackFactory->noEntrance(), Mockery::mock(WeaponType::class), $castType);
        $castAndAttack->setCharacterHealth(1000);
        $castAndAttack->setMonsterHealth(1000);
        $castAndAttack->setCharacterCastAndAttackkData($character, false);

        $result = $castAndAttack->handleAttack($character, $monster);

        $this->assertSame($castAndAttack, $result);
        $this->assertSame(0, $castAndAttack->getCharacterHealth());
        $this->assertSame(500, $castAndAttack->getMonsterHealth());
    }

    public function test_handle_attack_runs_both_cast_and_weapon_when_the_character_survives(): void
    {
        $character = $this->castAndAttackFactory->buildCharacter();
        $monster = $this->castAndAttackFactory->buildMonster();

        $castType = Mockery::mock(CastType::class);
        $castType->shouldReceive('setMonsterHealth')->once();
        $castType->shouldReceive('setCharacterHealth')->once();
        $castType->shouldReceive('setCharacterCastAndAttack')->once();
        $castType->shouldReceive('castAttack')->once();
        $castType->shouldReceive('getMessages')->once()->andReturn([]);
        $castType->shouldReceive('getCharacterHealth')->once()->andReturn(900);
        $castType->shouldReceive('getMonsterHealth')->once()->andReturn(500);
        $castType->shouldReceive('resetMessages')->once();

        $weaponType = Mockery::mock(WeaponType::class);
        $weaponType->shouldReceive('setMonsterHealth')->once();
        $weaponType->shouldReceive('setCharacterHealth')->once();
        $weaponType->shouldReceive('setCharacterAttackData')->once();
        $weaponType->shouldReceive('doNotAllowSecondaryAttacks')->once();
        $weaponType->shouldReceive('doWeaponAttack')->once();
        $weaponType->shouldReceive('getMessages')->once()->andReturn([]);
        $weaponType->shouldReceive('getCharacterHealth')->once()->andReturn(800);
        $weaponType->shouldReceive('getMonsterHealth')->once()->andReturn(200);
        $weaponType->shouldReceive('resetMessages')->once();

        $castAndAttack = $this->castAndAttackFactory->buildCastAndAttack($this->castAndAttackFactory->noEntrance(2), $weaponType, $castType);
        $castAndAttack->setCharacterHealth(1000);
        $castAndAttack->setMonsterHealth(1000);
        $castAndAttack->setCharacterCastAndAttackkData($character, false);

        $castAndAttack->handleAttack($character, $monster);

        $this->assertSame(800, $castAndAttack->getCharacterHealth());
        $this->assertSame(200, $castAndAttack->getMonsterHealth());
    }
}
