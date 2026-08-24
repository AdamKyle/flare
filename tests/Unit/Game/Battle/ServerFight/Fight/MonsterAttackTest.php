<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight;

use App\Game\Battle\ServerFight\Fight\CanHit;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Counter;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\PlayerHealing;
use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Battle\ServerFight\MonsterAttackFactory;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class MonsterAttackTest extends TestCase
{
    use RefreshDatabase;

    private MonsterAttackFactory $monsterAttackFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monsterAttackFactory = new MonsterAttackFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->monsterAttackFactory);
    }

    public function test_monster_attack_records_a_miss_message_when_the_monster_cannot_hit(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
            'affix_damage_reduction' => 0.0,
        ]);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canMonsterHitPlayer')->once()->andReturn(false);
        $canHit->shouldReceive('canMonsterCastSpell')->once()->andReturn(false);

        $playerHealing = Mockery::mock(PlayerHealing::class);
        $playerHealing->shouldReceive('setMonsterHealth');
        $playerHealing->shouldReceive('setCharacterHealth');
        $playerHealing->shouldReceive('healInBattle');
        $playerHealing->shouldReceive('lifeSteal');
        $playerHealing->shouldReceive('getCharacterHealth')->andReturnUsing(fn () => 1000);
        $playerHealing->shouldReceive('getMonsterHealth')->andReturnUsing(fn () => 1000);
        $playerHealing->shouldReceive('getMessages')->andReturn([]);
        $playerHealing->shouldReceive('clearMessages');

        $monsterAttack = $this->monsterAttackFactory->buildMonsterAttack(playerHealing: $playerHealing, canHit: $canHit);
        $monsterAttack->setCharacterHealth(1000);
        $monsterAttack->setMonsterHealth(1000);

        $monsterRandomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $monsterRandomNumberGenerator->shouldReceive('numberBetween')->andReturnUsing(fn ($min, $max) => $min);
        $monster = (new ServerMonster(new ChanceCalculator($monsterRandomNumberGenerator), $monsterRandomNumberGenerator))->setMonster([
            'name' => 'Test Monster',
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'fire_atonement' => 0,
            'ice_atonement' => 0,
            'water_atonement' => 0,
            'criticality' => 0.0,
            'attack_range' => '50-50',
            'increases_damage_by' => null,
            'max_affix_damage' => 0,
        ]);

        $monsterAttack->monsterAttack($monster, $character, 'attack');

        $this->assertContains([
            'message' => 'Test Monster misses!',
            'type' => 'enemy-action',
        ], $monsterAttack->getMessages());
    }

    public function test_monster_attack_stops_immediately_when_the_monster_dies_from_the_counter(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
            'ac' => 10_000,
        ]);
        Cache::put('character-attack-data-'.$character->id, [
            'attack_types' => ['attack' => ['weapon_damage' => 100]],
        ]);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canMonsterHitPlayer')->once()->andReturn(true);

        $counter = Mockery::mock(Counter::class);
        $counter->shouldReceive('setCharacterHealth');
        $counter->shouldReceive('setMonsterHealth');
        $counter->shouldReceive('setIsAttackerVoided');
        $counter->shouldReceive('playerCounter');
        $counter->shouldReceive('getMessages')->andReturn([]);
        $counter->shouldReceive('getCharacterHealth')->andReturn(1000);
        $counter->shouldReceive('getMonsterHealth')->andReturn(0);
        $counter->shouldReceive('clearMessages');

        $playerHealing = Mockery::mock(PlayerHealing::class);
        $playerHealing->shouldReceive('setMonsterHealth');
        $playerHealing->shouldReceive('setCharacterHealth');
        $playerHealing->shouldReceive('healInBattle');
        $playerHealing->shouldReceive('lifeSteal');
        $playerHealing->shouldReceive('getCharacterHealth')->andReturnUsing(fn () => 1000);
        $playerHealing->shouldReceive('getMonsterHealth')->andReturnUsing(fn () => 1000);
        $playerHealing->shouldReceive('getMessages')->andReturn([]);
        $playerHealing->shouldReceive('clearMessages');

        $monsterAttack = $this->monsterAttackFactory->buildMonsterAttack(playerHealing: $playerHealing, canHit: $canHit, counter: $counter);
        $monsterAttack->setCharacterHealth(1000);
        $monsterAttack->setMonsterHealth(1000);

        $monsterRandomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $monsterRandomNumberGenerator->shouldReceive('numberBetween')->andReturnUsing(fn ($min, $max) => $min);
        $monster = (new ServerMonster(new ChanceCalculator($monsterRandomNumberGenerator), $monsterRandomNumberGenerator))->setMonster([
            'name' => 'Test Monster',
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'fire_atonement' => 0,
            'ice_atonement' => 0,
            'water_atonement' => 0,
            'criticality' => 0.0,
            'attack_range' => '0-0',
            'increases_damage_by' => null,
            'ac' => 0,
        ]);

        $monsterAttack->monsterAttack($monster, $character, 'attack');

        $this->assertSame(0, $monsterAttack->getMonsterHealth());
    }

    public function test_monster_attack_resurrects_the_player_when_they_die(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
            'affix_damage_reduction' => 0.0,
        ]);
        Cache::put('character-attack-data-'.$character->id, [
            'attack_types' => ['attack' => ['res_chance' => 1.0]],
        ]);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canMonsterHitPlayer')->once()->andReturn(false);
        $canHit->shouldReceive('canMonsterCastSpell')->once()->andReturn(false);

        $playerHealing = Mockery::mock(PlayerHealing::class);
        $playerHealing->shouldReceive('setMonsterHealth');
        $playerHealing->shouldReceive('setCharacterHealth');
        $playerHealing->shouldReceive('healInBattle');
        $playerHealing->shouldReceive('lifeSteal');
        $playerHealing->shouldReceive('resurrect')->once();
        $playerHealing->shouldReceive('getCharacterHealth')->andReturnUsing(fn () => 0);
        $playerHealing->shouldReceive('getMonsterHealth')->andReturnUsing(fn () => 1000);
        $playerHealing->shouldReceive('getMessages')->andReturn([]);
        $playerHealing->shouldReceive('clearMessages');

        $monsterAttack = $this->monsterAttackFactory->buildMonsterAttack(playerHealing: $playerHealing, canHit: $canHit);
        $monsterAttack->setCharacterHealth(0);
        $monsterAttack->setMonsterHealth(1000);

        $monsterRandomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $monsterRandomNumberGenerator->shouldReceive('numberBetween')->andReturnUsing(fn ($min, $max) => $min);
        $monster = (new ServerMonster(new ChanceCalculator($monsterRandomNumberGenerator), $monsterRandomNumberGenerator))->setMonster([
            'name' => 'Test Monster',
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'fire_atonement' => 0,
            'ice_atonement' => 0,
            'water_atonement' => 0,
            'criticality' => 0.0,
            'attack_range' => '50-50',
            'increases_damage_by' => null,
            'max_affix_damage' => 0,
        ]);

        $monsterAttack->monsterAttack($monster, $character, 'attack');

        $this->assertSame(0, $monsterAttack->getCharacterHealth());
    }

    public function test_monster_attack_skips_enchantments_and_spells_when_enemy_is_voided(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
        ]);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canMonsterHitPlayer')->once()->andReturn(false);

        $playerHealing = Mockery::mock(PlayerHealing::class);
        $playerHealing->shouldReceive('setMonsterHealth');
        $playerHealing->shouldReceive('setCharacterHealth');
        $playerHealing->shouldReceive('healInBattle');
        $playerHealing->shouldReceive('lifeSteal');
        $playerHealing->shouldReceive('getCharacterHealth')->andReturnUsing(fn () => 1000);
        $playerHealing->shouldReceive('getMonsterHealth')->andReturnUsing(fn () => 1000);
        $playerHealing->shouldReceive('getMessages')->andReturn([]);
        $playerHealing->shouldReceive('clearMessages');

        $monsterAttack = $this->monsterAttackFactory->buildMonsterAttack(playerHealing: $playerHealing, canHit: $canHit);
        $monsterAttack->setCharacterHealth(1000);
        $monsterAttack->setMonsterHealth(1000);
        $monsterAttack->setIsEnemyVoided(true);

        $monsterRandomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $monsterRandomNumberGenerator->shouldReceive('numberBetween')->andReturnUsing(fn ($min, $max) => $min);
        $monster = (new ServerMonster(new ChanceCalculator($monsterRandomNumberGenerator), $monsterRandomNumberGenerator))->setMonster([
            'name' => 'Test Monster',
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'fire_atonement' => 0,
            'ice_atonement' => 0,
            'water_atonement' => 0,
            'criticality' => 0.0,
            'attack_range' => '50-50',
            'increases_damage_by' => null,
        ]);

        $monsterAttack->monsterAttack($monster, $character, 'attack');

        $this->assertContains([
            'message' => 'Test Monster misses!',
            'type' => 'enemy-action',
        ], $monsterAttack->getMessages());
    }

    public function test_get_last_rolled_attack_returns_zero_by_default(): void
    {
        $monsterAttack = $this->monsterAttackFactory->buildMonsterAttack();

        $this->assertSame(0, $monsterAttack->getLastRolledAttack());
    }

    public function test_set_is_character_voided_is_fluent(): void
    {
        $monsterAttack = $this->monsterAttackFactory->buildMonsterAttack();

        $this->assertSame($monsterAttack, $monsterAttack->setIsCharacterVoided(true));
    }
}
