<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\Types;

use App\Game\Battle\ServerFight\Fight\CanHit;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\Defend;
use App\Game\Battle\ServerFight\Fight\Entrance;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Battle\ServerFight\CharacterCacheDataFactory;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class DefendTest extends TestCase
{
    use RefreshDatabase;

    public function test_defend_caches_the_characters_defence_ac_and_merges_secondary_attack_messages(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter([], [], assignPassiveSkills: false)->getCharacter();

        $secondaryAttacks = Mockery::mock(SecondaryAttacks::class);
        $secondaryAttacks->shouldReceive('setMonsterHealth')->once();
        $secondaryAttacks->shouldReceive('setCharacterHealth')->once();
        $secondaryAttacks->shouldReceive('setAttackData')->once();
        $secondaryAttacks->shouldReceive('affixLifeStealingDamage')->once();
        $secondaryAttacks->shouldReceive('affixDamage')->once();
        $secondaryAttacks->shouldReceive('ringDamage')->once();
        $secondaryAttacks->shouldReceive('getMessages')->once()->andReturn([['message' => 'ring hit', 'type' => 'player-action']]);
        $secondaryAttacks->shouldReceive('clearMessages')->once();

        $entrance = Mockery::mock(Entrance::class);
        $entrance->shouldReceive('playerEntrance')->once();
        $entrance->shouldReceive('getMessages')->once()->andReturn([]);
        $entrance->shouldReceive('isEnemyEntranced')->once()->andReturn(false);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        $defend = new Defend(
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
            $entrance,
            Mockery::mock(CanHit::class),
            $secondaryAttacks,
            Mockery::mock(SpecialAttacks::class),
        );
        $defend->setCharacterHealth(1000);
        $defend->setMonsterHealth(1000);

        Cache::put('character-attack-data-'.$character->id, [
            'attack_types' => ['defend' => [
                'defence' => 50,
                'special_damage' => [],
                'attack_type' => 'defend',
            ]],
        ]);
        $defend->setCharacterAttackData($character, false);

        $monsterRandomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $monster = (new ServerMonster(new ChanceCalculator($monsterRandomNumberGenerator), $monsterRandomNumberGenerator))->setMonster(['is_raid_boss' => false]);

        $defend->defend($character, $monster);

        $this->assertSame(50, (new CharacterCacheDataFactory())->build()->getCharacterDefenceAc($character));
        $this->assertContains(['message' => 'ring hit', 'type' => 'player-action'], $defend->getMessages());
    }

    public function test_defend_still_runs_the_secondary_attack_when_the_enemy_becomes_entranced(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter([], [], assignPassiveSkills: false)->getCharacter();

        $secondaryAttacks = Mockery::mock(SecondaryAttacks::class);
        $secondaryAttacks->shouldReceive('setMonsterHealth');
        $secondaryAttacks->shouldReceive('setCharacterHealth');
        $secondaryAttacks->shouldReceive('setAttackData');
        $secondaryAttacks->shouldReceive('affixLifeStealingDamage');
        $secondaryAttacks->shouldReceive('affixDamage');
        $secondaryAttacks->shouldReceive('ringDamage');
        $secondaryAttacks->shouldReceive('getMessages')->andReturn([]);
        $secondaryAttacks->shouldReceive('clearMessages');

        $entrance = Mockery::mock(Entrance::class);
        $entrance->shouldReceive('playerEntrance')->once();
        $entrance->shouldReceive('getMessages')->once()->andReturn([]);
        $entrance->shouldReceive('isEnemyEntranced')->once()->andReturn(true);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        $defend = new Defend(
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
            $entrance,
            Mockery::mock(CanHit::class),
            $secondaryAttacks,
            Mockery::mock(SpecialAttacks::class),
        );
        $defend->setCharacterHealth(1000);
        $defend->setMonsterHealth(1000);

        Cache::put('character-attack-data-'.$character->id, [
            'attack_types' => ['defend' => [
                'defence' => 50,
                'special_damage' => [],
                'attack_type' => 'defend',
            ]],
        ]);
        $defend->setCharacterAttackData($character, false);

        $monsterRandomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $monster = (new ServerMonster(new ChanceCalculator($monsterRandomNumberGenerator), $monsterRandomNumberGenerator))->setMonster(['is_raid_boss' => false]);

        $result = $defend->defend($character, $monster);

        $this->assertSame($defend, $result);
    }

    public function test_reset_messages_clears_both_own_and_entrance_messages(): void
    {
        $entrance = Mockery::mock(Entrance::class);
        $entrance->shouldReceive('clearMessages')->once();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        $secondaryAttacks = Mockery::mock(SecondaryAttacks::class);
        $secondaryAttacks->shouldReceive('setMonsterHealth');
        $secondaryAttacks->shouldReceive('setCharacterHealth');
        $secondaryAttacks->shouldReceive('setAttackData');
        $secondaryAttacks->shouldReceive('affixLifeStealingDamage');
        $secondaryAttacks->shouldReceive('affixDamage');
        $secondaryAttacks->shouldReceive('ringDamage');
        $secondaryAttacks->shouldReceive('getMessages')->andReturn([]);
        $secondaryAttacks->shouldReceive('clearMessages');

        $defend = new Defend(
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
            $entrance,
            Mockery::mock(CanHit::class),
            $secondaryAttacks,
            Mockery::mock(SpecialAttacks::class),
        );

        $defend->resetMessages();

        $this->assertEmpty($defend->getMessages());
    }
}
