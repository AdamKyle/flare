<?php

namespace Tests\Unit\Game\Battle\ServerFight;

use App\Game\Battle\ServerFight\Fight\Ambush;
use App\Game\Battle\ServerFight\Fight\Attack;
use App\Game\Battle\ServerFight\Fight\Voidance;
use App\Game\Battle\ServerFight\Monster\BuildMonster;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Exploration\Services\DelveMonsterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Battle\ServerFight\MonsterPlayerFightFactory;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class MonsterPlayerFightTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_character_resets_forced_health_and_stores_the_character(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $fight = (new MonsterPlayerFightFactory())->build();
        $result = $fight->setCharacter($character);

        $this->assertSame($fight, $result);
    }

    public function test_set_up_fight_returns_an_error_when_no_monster_is_found(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $fight = (new MonsterPlayerFightFactory())->build();
        $result = $fight->setUpFight($character, [
            'cached_monster' => [],
            'attack_type' => 'attack',
        ]);

        $this->assertSame([
            'message' => 'No monster was found.',
            'status' => 422,
        ], $result);
    }

    public function test_set_up_fight_uses_the_cached_monster_when_provided(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $fight = (new MonsterPlayerFightFactory())->build();
        $result = $fight->setUpFight($character, [
            'cached_monster' => ['id' => 5, 'name' => 'Cached'],
            'attack_type' => 'attack',
        ]);

        $this->assertSame($fight, $result);
        $this->assertSame(['id' => 5, 'name' => 'Cached'], $fight->getMonster());
    }

    public function test_set_up_fight_increases_strength_and_caches_when_pack_size_exceeds_one(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = new MonsterPlayerFightFactory();

        Cache::put('monsters', [
            'Surface' => ['data' => [
                ['id' => 5, 'name' => 'Fresh', 'fire_atonement' => 0, 'ice_atonement' => 0, 'water_atonement' => 0],
            ]],
        ]);

        $buildMonster = Mockery::mock(BuildMonster::class);
        $buildMonster->shouldReceive('setServerMonster')->once()->andReturnUsing(function ($monster) use ($factory) {
            return $factory->buildServerMonster($monster);
        });

        $delveMonsterService = Mockery::mock(DelveMonsterService::class);
        $delveMonsterService->shouldReceive('createMonster')
            ->once()
            ->andReturn(['id' => 5, 'name' => 'Fresh', 'strengthened' => true]);

        $fight = $factory->build(buildMonster: $buildMonster, delveMonsterService: $delveMonsterService);
        $fight->setUpFight($character, [
            'selected_monster_id' => 5,
            'attack_type' => 'attack',
            'pack_size' => 2,
        ], true);

        $this->assertSame(['id' => 5, 'name' => 'Fresh', 'strengthened' => true], Cache::get('delve-monster-'.$character->id.'-5-fight'));
    }

    public function test_set_up_raid_fight_stores_the_raid_monster_and_resets_forced_health(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $fight = (new MonsterPlayerFightFactory())->build();
        $result = $fight->setUpRaidFight($character, ['id' => 9, 'name' => 'Raid Boss'], 'attack');

        $this->assertSame($fight, $result);
        $this->assertSame(['id' => 9, 'name' => 'Raid Boss'], $fight->getMonster());
    }

    public function test_reset_battle_messages_clears_all_collaborator_messages(): void
    {
        $voidance = Mockery::mock(Voidance::class);
        $voidance->shouldReceive('clearMessages')->once();

        $ambush = Mockery::mock(Ambush::class);
        $ambush->shouldReceive('clearMessages')->once();

        $attack = Mockery::mock(Attack::class);
        $attack->shouldReceive('resetBattleMessages')->once();

        $fight = (new MonsterPlayerFightFactory())->build(voidance: $voidance, ambush: $ambush, attack: $attack);
        $fight->resetBattleMessages();

        $this->assertEmpty($fight->getBattleMessages());
    }

    public function test_get_character_and_monster_health_delegate_to_attack(): void
    {
        $attack = Mockery::mock(Attack::class);
        $attack->shouldReceive('getCharacterHealth')->once()->andReturn(500);
        $attack->shouldReceive('getMonsterHealth')->once()->andReturn(600);
        $attack->shouldReceive('getMonsterLastRolledAttack')->once()->andReturn(42);

        $fight = (new MonsterPlayerFightFactory())->build(attack: $attack);

        $this->assertSame(500, $fight->getCharacterHealth());
        $this->assertSame(600, $fight->getMonsterHealth());
        $this->assertSame(42, $fight->getMonsterLastRolledAttack());
    }

    public function test_fight_setup_builds_health_and_assembles_the_response(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = new MonsterPlayerFightFactory();

        $characterCacheData = Mockery::mock(CharacterCacheData::class);
        $characterCacheData->shouldReceive('getCachedCharacterData')->once()->with($character, 'stat_affixes')->andReturn(['stat_reduction' => []]);
        $characterCacheData->shouldReceive('getCachedCharacterData')->once()->with($character, 'skill_reduction')->andReturn(0.0);
        $characterCacheData->shouldReceive('getCachedCharacterData')->once()->with($character, 'resistance_reduction')->andReturn(0.0);
        $characterCacheData->shouldReceive('getCachedCharacterData')->once()->with($character, 'health')->andReturn(1000);

        $serverMonster = $factory->buildServerMonster();

        $buildMonster = Mockery::mock(BuildMonster::class);
        $buildMonster->shouldReceive('buildMonster')->once()->andReturn($serverMonster);
        $buildMonster->shouldReceive('getMessages')->once()->andReturn([]);

        $voidance = Mockery::mock(Voidance::class);
        $voidance->shouldReceive('void')->once();
        $voidance->shouldReceive('getMessages')->once()->andReturn([]);
        $voidance->shouldReceive('isPlayerVoided')->once()->andReturn(false);
        $voidance->shouldReceive('isEnemyVoided')->once()->andReturn(false);

        $ambush = Mockery::mock(Ambush::class);
        $ambush->shouldReceive('handleAmbush')->once()->andReturnSelf();
        $ambush->shouldReceive('getHealthObject')->once()->andReturn([
            'current_character_health' => 900,
            'current_monster_health' => 950,
        ]);
        $ambush->shouldReceive('getMessages')->once()->andReturn([]);

        $fight = $factory->build(
            buildMonster: $buildMonster,
            characterCacheData: $characterCacheData,
            voidance: $voidance,
            ambush: $ambush,
        );
        $fight->setUpRaidFight($character, ['id' => 1], 'attack');

        $result = $fight->fightSetUp();

        $this->assertSame(900, $result['health']['current_character_health']);
        $this->assertSame(950, $result['health']['current_monster_health']);
        $this->assertSame(1000, $result['health']['max_character_health']);
        $this->assertSame(1000, $result['health']['max_monster_health']);
        $this->assertSame(1, $result['monster_id']);
        $this->assertFalse($result['player_voided']);
        $this->assertFalse($result['enemy_voided']);
    }

    public function test_fight_setup_applies_the_forced_current_and_max_monster_health(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = new MonsterPlayerFightFactory();

        $characterCacheData = Mockery::mock(CharacterCacheData::class);
        $characterCacheData->shouldReceive('getCachedCharacterData')->once()->with($character, 'stat_affixes')->andReturn(['stat_reduction' => []]);
        $characterCacheData->shouldReceive('getCachedCharacterData')->once()->with($character, 'skill_reduction')->andReturn(0.0);
        $characterCacheData->shouldReceive('getCachedCharacterData')->once()->with($character, 'resistance_reduction')->andReturn(0.0);
        $characterCacheData->shouldReceive('getCachedCharacterData')->once()->with($character, 'health')->andReturn(1000);

        $serverMonster = $factory->buildServerMonster();

        $buildMonster = Mockery::mock(BuildMonster::class);
        $buildMonster->shouldReceive('buildMonster')->once()->andReturn($serverMonster);
        $buildMonster->shouldReceive('getMessages')->once()->andReturn([]);

        $voidance = Mockery::mock(Voidance::class);
        $voidance->shouldReceive('void')->once();
        $voidance->shouldReceive('getMessages')->once()->andReturn([]);
        $voidance->shouldReceive('isPlayerVoided')->once()->andReturn(false);
        $voidance->shouldReceive('isEnemyVoided')->once()->andReturn(false);

        $ambush = Mockery::mock(Ambush::class);
        $ambush->shouldReceive('handleAmbush')->once()->andReturnSelf();
        $ambush->shouldReceive('getHealthObject')->once()->andReturn([
            'current_character_health' => 900,
            'current_monster_health' => 9000,
        ]);
        $ambush->shouldReceive('getMessages')->once()->andReturn([]);

        $fight = $factory->build(
            buildMonster: $buildMonster,
            characterCacheData: $characterCacheData,
            voidance: $voidance,
            ambush: $ambush,
        );
        $fight->setUpFight($character, [
            'cached_monster' => ['id' => 1],
            'attack_type' => 'attack',
            'current_monster_health' => 500,
            'max_monster_health' => 800,
        ]);

        $result = $fight->fightSetUp();

        $this->assertSame(500, $serverMonster->getHealth());
        $this->assertSame(800, $result['health']['max_monster_health']);
        $this->assertSame(800, $result['health']['current_monster_health']);
    }

    public function test_fight_monster_uses_the_cached_monster_when_present(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = new MonsterPlayerFightFactory();

        Cache::put('monster-fight-'.$character->id, [
            'monster' => ['id' => 1, 'name' => 'Cached Fight Monster'],
            'health' => ['current_character_health' => 0, 'current_monster_health' => 1000],
            'player_voided' => false,
            'enemy_voided' => false,
        ]);

        $attack = Mockery::mock(Attack::class);
        $attack->shouldReceive('setHealth')->once()->andReturnSelf();
        $attack->shouldReceive('setIsCharacterVoided')->once()->andReturnSelf();
        $attack->shouldReceive('setIsEnemyVoided')->once()->andReturnSelf();
        $attack->shouldReceive('onlyAttackOnce')->once()->andReturnSelf();

        $buildMonster = Mockery::mock(BuildMonster::class);
        $buildMonster->shouldReceive('setServerMonster')->once()->andReturn($factory->buildServerMonster());

        $fight = $factory->build(attack: $attack, buildMonster: $buildMonster);
        $fight->setCharacter($character);

        $result = $fight->fightMonster();

        $this->assertFalse($result);
        $this->assertContains([
            'message' => 'The enemies ambush has slaughtered you!',
            'type' => 'enemy-action',
        ], $fight->getBattleMessages());
    }

    public function test_fight_monster_overrides_the_attack_type_when_provided(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = new MonsterPlayerFightFactory();

        Cache::put('monster-fight-'.$character->id, [
            'monster' => ['id' => 1, 'name' => 'Cached Fight Monster'],
            'health' => ['current_character_health' => 0, 'current_monster_health' => 1000],
            'player_voided' => false,
            'enemy_voided' => false,
        ]);

        $attack = Mockery::mock(Attack::class);
        $attack->shouldReceive('setHealth')->once()->andReturnSelf();
        $attack->shouldReceive('setIsCharacterVoided')->once()->andReturnSelf();
        $attack->shouldReceive('setIsEnemyVoided')->once()->andReturnSelf();
        $attack->shouldReceive('onlyAttackOnce')->once()->andReturnSelf();

        $buildMonster = Mockery::mock(BuildMonster::class);
        $buildMonster->shouldReceive('setServerMonster')->once()->andReturn($factory->buildServerMonster());

        $fight = $factory->build(attack: $attack, buildMonster: $buildMonster);
        $fight->setCharacter($character);

        $result = $fight->fightMonster(false, 'cast');

        $this->assertFalse($result);
    }

    public function test_fight_monster_runs_fight_setup_when_there_is_no_cached_fight(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = new MonsterPlayerFightFactory();

        Cache::forget('monster-fight-'.$character->id);

        $characterCacheData = Mockery::mock(CharacterCacheData::class);
        $characterCacheData->shouldReceive('getCachedCharacterData')->once()->with($character, 'stat_affixes')->andReturn(['stat_reduction' => []]);
        $characterCacheData->shouldReceive('getCachedCharacterData')->once()->with($character, 'skill_reduction')->andReturn(0.0);
        $characterCacheData->shouldReceive('getCachedCharacterData')->once()->with($character, 'resistance_reduction')->andReturn(0.0);
        $characterCacheData->shouldReceive('getCachedCharacterData')->once()->with($character, 'health')->andReturn(1000);

        $serverMonster = $factory->buildServerMonster();

        $buildMonster = Mockery::mock(BuildMonster::class);
        $buildMonster->shouldReceive('buildMonster')->once()->andReturn($serverMonster);
        $buildMonster->shouldReceive('getMessages')->once()->andReturn([]);
        $buildMonster->shouldReceive('setServerMonster')->once()->andReturn($serverMonster);

        $voidance = Mockery::mock(Voidance::class);
        $voidance->shouldReceive('void')->once();
        $voidance->shouldReceive('getMessages')->once()->andReturn([]);
        $voidance->shouldReceive('isPlayerVoided')->once()->andReturn(false);
        $voidance->shouldReceive('isEnemyVoided')->once()->andReturn(false);

        $ambush = Mockery::mock(Ambush::class);
        $ambush->shouldReceive('handleAmbush')->once()->andReturnSelf();
        $ambush->shouldReceive('getHealthObject')->once()->andReturn([
            'current_character_health' => 0,
            'current_monster_health' => 1000,
        ]);
        $ambush->shouldReceive('getMessages')->once()->andReturn([]);

        $attack = Mockery::mock(Attack::class);
        $attack->shouldReceive('setHealth')->once()->andReturnSelf();
        $attack->shouldReceive('setIsCharacterVoided')->once()->andReturnSelf();
        $attack->shouldReceive('setIsEnemyVoided')->once()->andReturnSelf();
        $attack->shouldReceive('onlyAttackOnce')->once()->andReturnSelf();

        $fight = $factory->build(
            buildMonster: $buildMonster,
            characterCacheData: $characterCacheData,
            voidance: $voidance,
            ambush: $ambush,
            attack: $attack,
        );
        $fight->setUpRaidFight($character, ['id' => 1], 'attack');

        $result = $fight->fightMonster();

        $this->assertFalse($result);
    }

    public function test_process_attack_returns_false_when_the_character_is_already_dead(): void
    {
        $factory = new MonsterPlayerFightFactory();

        $attack = Mockery::mock(Attack::class);
        $attack->shouldReceive('setHealth')->once()->andReturnSelf();
        $attack->shouldReceive('setIsCharacterVoided')->once()->andReturnSelf();
        $attack->shouldReceive('setIsEnemyVoided')->once()->andReturnSelf();
        $attack->shouldReceive('onlyAttackOnce')->once()->andReturnSelf();

        $buildMonster = Mockery::mock(BuildMonster::class);
        $buildMonster->shouldReceive('setServerMonster')->once()->andReturn($factory->buildServerMonster());

        $fight = $factory->build(attack: $attack, buildMonster: $buildMonster);

        $result = $fight->processAttack([
            'monster' => ['id' => 1],
            'health' => ['current_character_health' => 0, 'current_monster_health' => 1000],
            'player_voided' => false,
            'enemy_voided' => false,
        ]);

        $this->assertFalse($result);
        $this->assertContains([
            'message' => 'The enemies ambush has slaughtered you!',
            'type' => 'enemy-action',
        ], $fight->getBattleMessages());
    }

    public function test_process_attack_returns_true_when_the_monster_is_already_dead(): void
    {
        $factory = new MonsterPlayerFightFactory();

        $attack = Mockery::mock(Attack::class);
        $attack->shouldReceive('setHealth')->once()->andReturnSelf();
        $attack->shouldReceive('setIsCharacterVoided')->once()->andReturnSelf();
        $attack->shouldReceive('setIsEnemyVoided')->once()->andReturnSelf();
        $attack->shouldReceive('onlyAttackOnce')->once()->andReturnSelf();

        $buildMonster = Mockery::mock(BuildMonster::class);
        $buildMonster->shouldReceive('setServerMonster')->once()->andReturn($factory->buildServerMonster());

        $fight = $factory->build(attack: $attack, buildMonster: $buildMonster);

        $result = $fight->processAttack([
            'monster' => ['id' => 1],
            'health' => ['current_character_health' => 1000, 'current_monster_health' => 0],
            'player_voided' => false,
            'enemy_voided' => false,
        ]);

        $this->assertTrue($result);
        $this->assertContains([
            'message' => 'Your ambush has slaughtered the enemy!',
            'type' => 'enemy-action',
        ], $fight->getBattleMessages());
    }

    public function test_process_attack_calls_do_attack_when_both_sides_are_alive(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = new MonsterPlayerFightFactory();

        $attack = Mockery::mock(Attack::class);
        $attack->shouldReceive('setHealth')->once()->andReturnSelf();
        $attack->shouldReceive('setIsCharacterVoided')->once()->andReturnSelf();
        $attack->shouldReceive('setIsEnemyVoided')->once()->andReturnSelf();
        $attack->shouldReceive('onlyAttackOnce')->once()->andReturnSelf();
        $attack->shouldReceive('attack')->once();
        $attack->shouldReceive('getMessages')->once()->andReturn([]);
        $attack->shouldReceive('resetBattleMessages')->once();
        $attack->shouldReceive('getCharacterHealth')->once()->andReturn(1000);
        $attack->shouldReceive('getMonsterHealth')->once()->andReturn(500);
        $attack->shouldReceive('tookTooLong')->once()->andReturn(false);

        $buildMonster = Mockery::mock(BuildMonster::class);
        $buildMonster->shouldReceive('setServerMonster')->once()->andReturn($factory->buildServerMonster());

        $fight = $factory->build(attack: $attack, buildMonster: $buildMonster);
        $fight->setUpRaidFight($character, ['id' => 1], 'attack');

        $result = $fight->processAttack([
            'monster' => ['id' => 1],
            'health' => ['current_character_health' => 1000, 'current_monster_health' => 1000],
            'player_voided' => false,
            'enemy_voided' => false,
        ]);

        $this->assertFalse($result);
    }
}
