<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\Fight\CanHit;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Counter;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\CastType;
use App\Game\Battle\ServerFight\Fight\Entrance;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Character\CharacterFactory;

class CastTypeFactory
{
    public function build(
        ?Entrance $entrance = null,
        ?CanHit $canHit = null,
        ?SpecialAttacks $specialAttacks = null,
        ?SecondaryAttacks $secondaryAttacks = null,
        ?Counter $counter = null,
        ?ChanceCalculator $chanceCalculator = null,
    ): CastType {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        return new CastType(
            (new CharacterCacheDataFactory())->build(),
            $chanceCalculator ?? new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
            $entrance ?? Mockery::mock(Entrance::class),
            $canHit ?? Mockery::mock(CanHit::class),
            $specialAttacks ?? $this->noOpSpecialAttacks(),
            $secondaryAttacks ?? Mockery::mock(SecondaryAttacks::class),
            $counter ?? Mockery::mock(Counter::class),
        );
    }

    public function noOpSpecialAttacks(): SpecialAttacks
    {
        $state = ['character' => 0, 'monster' => 0];

        $specialAttacks = Mockery::mock(SpecialAttacks::class);
        $specialAttacks->shouldReceive('setCharacterHealth')->andReturnUsing(function ($value) use (&$state, $specialAttacks) {
            $state['character'] = $value;

            return $specialAttacks;
        });
        $specialAttacks->shouldReceive('setMonsterHealth')->andReturnUsing(function ($value) use (&$state, $specialAttacks) {
            $state['monster'] = $value;

            return $specialAttacks;
        });
        $specialAttacks->shouldReceive('setIsRaidBoss')->andReturnSelf();
        $specialAttacks->shouldReceive('doCastDamageSpecials');
        $specialAttacks->shouldReceive('doCastHealSpecials')->andReturnSelf();
        $specialAttacks->shouldReceive('getHealFor')->andReturn(0);
        $specialAttacks->shouldReceive('getMessages')->andReturn([]);
        $specialAttacks->shouldReceive('getCharacterHealth')->andReturnUsing(function () use (&$state) {
            return $state['character'];
        });
        $specialAttacks->shouldReceive('getMonsterHealth')->andReturnUsing(function () use (&$state) {
            return $state['monster'];
        });
        $specialAttacks->shouldReceive('clearMessages');

        return $specialAttacks;
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
            'spell_evasion' => 0.0,
        ], $monster));
    }

    public function seedCharacterSheet(Character $character, array $overrides = []): void
    {
        Cache::put('character-sheet-'.$character->id, array_merge([
            'level' => $character->level,
            'skills' => ['criticality' => 0.0],
            'health' => 1000,
        ], $overrides));
    }

    public function setAttackData(Character $character, CastType $castType, array $overrides = []): void
    {
        Cache::put('character-attack-data-'.$character->id, [
            'attack_types' => ['cast' => array_merge([
                'spell_damage' => 100,
                'heal_for' => 0,
                'damage_deduction' => 0.0,
            ], $overrides)],
        ]);
        $castType->setCharacterAttackData($character, false, 'cast');
    }

    public function mockSecondaryAttack(int $characterHealth, int $monsterHealth): SecondaryAttacks
    {
        $secondaryAttacks = Mockery::mock(SecondaryAttacks::class);
        $secondaryAttacks->shouldReceive('setIsRaidBoss');
        $secondaryAttacks->shouldReceive('setMonsterHealth');
        $secondaryAttacks->shouldReceive('setCharacterHealth');
        $secondaryAttacks->shouldReceive('setAttackData');
        $secondaryAttacks->shouldReceive('setIsCharacterVoided');
        $secondaryAttacks->shouldReceive('setIsEnemyEntranced');
        $secondaryAttacks->shouldReceive('setDefenderId');
        $secondaryAttacks->shouldReceive('doSecondaryAttack');
        $secondaryAttacks->shouldReceive('getMonsterHealth')->andReturn($monsterHealth);
        $secondaryAttacks->shouldReceive('getCharacterHealth')->andReturn($characterHealth);
        $secondaryAttacks->shouldReceive('getMessages')->andReturn([]);
        $secondaryAttacks->shouldReceive('clearMessages');

        return $secondaryAttacks;
    }

    public function mockMonsterCounter(int $characterHealth, int $monsterHealth): Counter
    {
        $counter = Mockery::mock(Counter::class);
        $counter->shouldReceive('setCharacterHealth');
        $counter->shouldReceive('setMonsterHealth');
        $counter->shouldReceive('setIsAttackerVoided');
        $counter->shouldReceive('monsterCounter');
        $counter->shouldReceive('getMessages')->andReturn([]);
        $counter->shouldReceive('getCharacterHealth')->andReturn($characterHealth);
        $counter->shouldReceive('getMonsterHealth')->andReturn($monsterHealth);
        $counter->shouldReceive('clearMessages');

        return $counter;
    }
}
