<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\Fight\CanHit;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Counter;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\WeaponType;
use App\Game\Battle\ServerFight\Fight\Entrance;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Character\CharacterFactory;

class WeaponTypeFactory
{
    public function build(
        ?Entrance $entrance = null,
        ?CanHit $canHit = null,
        ?SpecialAttacks $specialAttacks = null,
        ?SecondaryAttacks $secondaryAttacks = null,
        ?Counter $counter = null,
    ): WeaponType {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new WeaponType(
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
            $entrance ?? Mockery::mock(Entrance::class),
            $canHit ?? Mockery::mock(CanHit::class),
            $specialAttacks ?? Mockery::mock(SpecialAttacks::class),
            $secondaryAttacks ?? Mockery::mock(SecondaryAttacks::class),
            $counter ?? Mockery::mock(Counter::class),
        );
    }

    public function buildCharacter(array $classOptions = []): Character
    {
        return (new CharacterFactory())->createBaseCharacter([], $classOptions, assignPassiveSkills: false)->getCharacter();
    }

    public function buildMonster(array $monster = []): ServerMonster
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster(array_merge([
            'id' => 1,
            'name' => 'Test Monster',
            'is_raid_boss' => false,
            'ac' => 10,
        ], $monster));
    }

    public function seedCharacterSheet(Character $character, array $overrides = []): void
    {
        Cache::put('character-sheet-'.$character->id, array_merge([
            'level' => $character->level,
            'skills' => ['criticality' => 0.0],
        ], $overrides));
    }

    public function stubSpecialAttacksNoOp(SpecialAttacks $specialAttacks, int $characterHealth, int $monsterHealth): void
    {
        $specialAttacks->shouldReceive('setCharacterHealth')->once()->andReturnSelf();
        $specialAttacks->shouldReceive('setMonsterHealth')->once()->andReturnSelf();
        $specialAttacks->shouldReceive('setIsRaidBoss')->once()->andReturnSelf();
        $specialAttacks->shouldReceive('doWeaponSpecials')->once();
        $specialAttacks->shouldReceive('getMessages')->once()->andReturn([]);
        $specialAttacks->shouldReceive('getCharacterHealth')->once()->andReturn($characterHealth);
        $specialAttacks->shouldReceive('getMonsterHealth')->once()->andReturn($monsterHealth);
        $specialAttacks->shouldReceive('clearMessages')->once();
    }

    public function mockSecondaryAttack(int $characterHealth, int $monsterHealth): SecondaryAttacks
    {
        $secondaryAttacks = Mockery::mock(SecondaryAttacks::class);
        $secondaryAttacks->shouldReceive('setIsRaidBoss')->once();
        $secondaryAttacks->shouldReceive('setMonsterHealth')->once();
        $secondaryAttacks->shouldReceive('setCharacterHealth')->once();
        $secondaryAttacks->shouldReceive('setAttackData')->once();
        $secondaryAttacks->shouldReceive('setIsCharacterVoided')->once();
        $secondaryAttacks->shouldReceive('setIsEnemyEntranced')->once();
        $secondaryAttacks->shouldReceive('setDefenderId')->once();
        $secondaryAttacks->shouldReceive('doSecondaryAttack')->once();
        $secondaryAttacks->shouldReceive('getMonsterHealth')->once()->andReturn($monsterHealth);
        $secondaryAttacks->shouldReceive('getCharacterHealth')->once()->andReturn($characterHealth);
        $secondaryAttacks->shouldReceive('getMessages')->once()->andReturn([]);
        $secondaryAttacks->shouldReceive('clearMessages')->once();

        return $secondaryAttacks;
    }

    public function mockMonsterCounter(int $characterHealth, int $monsterHealth): Counter
    {
        $counter = Mockery::mock(Counter::class);
        $counter->shouldReceive('setCharacterHealth')->once();
        $counter->shouldReceive('setMonsterHealth')->once();
        $counter->shouldReceive('setIsAttackerVoided')->once();
        $counter->shouldReceive('monsterCounter')->once();
        $counter->shouldReceive('getMessages')->once()->andReturn([]);
        $counter->shouldReceive('getCharacterHealth')->once()->andReturn($characterHealth);
        $counter->shouldReceive('getMonsterHealth')->once()->andReturn($monsterHealth);
        $counter->shouldReceive('clearMessages')->once();

        return $counter;
    }

    public function setAttackData(Character $character, WeaponType $weaponType, array $overrides = []): void
    {
        Cache::put('character-attack-data-'.$character->id, [
            'attack_types' => ['attack' => array_merge([
                'weapon_damage' => 100,
                'damage_deduction' => 0.0,
            ], $overrides)],
        ]);
        $weaponType->setCharacterAttackData($character, false, 'attack');
    }
}
