<?php

namespace Tests\Unit\Game\Battle\Services;

use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Raid;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\RaidBossParticipation;
use App\Flare\ServerFight\Monster\BuildMonster;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Flare\Services\BuildMonsterCacheService;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\RaidBattleService;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class RaidBattleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testRaidBossSetupShowsFiveAttacksWhenOnlyAnotherRaidHasParticipation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16)->getCharacter();
        $monster = Monster::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'is_raid_boss' => true,
        ]);
        $oldLocation = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 32,
        ]);
        $currentLocation = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 48,
            'y' => 48,
        ]);
        $oldRaid = Raid::factory()->create([
            'raid_boss_id' => $monster->id,
            'raid_boss_location_id' => $oldLocation->id,
        ]);
        $currentRaid = Raid::factory()->create([
            'raid_boss_id' => $monster->id,
            'raid_boss_location_id' => $currentLocation->id,
        ]);
        $raidBoss = RaidBoss::create([
            'raid_id' => $currentRaid->id,
            'raid_boss_id' => $monster->id,
            'boss_max_hp' => 100,
            'boss_current_hp' => 100,
            'raid_boss_deatils' => ['id' => $monster->id],
        ]);
        RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $oldRaid->id,
            'attacks_left' => 0,
            'damage_dealt' => 100,
            'killed_boss' => false,
        ]);

        Cache::put('raid-monsters', [
            $character->map->gameMap->name => [
                ['id' => $monster->id],
            ],
        ]);

        $serverMonster = Mockery::mock(ServerMonster::class);
        $serverMonster->shouldReceive('getElementData')->once()->andReturn([
            'fire' => 0,
            'ice' => 0,
            'water' => 0,
        ]);
        $serverMonster->shouldReceive('getHighestElementDamage')->once()->andReturn(0.0);
        $serverMonster->shouldReceive('getHighestElementName')->once()->andReturn('fire');

        $buildMonster = Mockery::mock(BuildMonster::class);
        $buildMonster->shouldReceive('buildMonster')->once()->andReturn($serverMonster);
        $characterCacheData = Mockery::mock(CharacterCacheData::class);
        $characterCacheData->shouldReceive('getCachedCharacterData')->with($character, 'stat_affixes')->andReturn([]);
        $characterCacheData->shouldReceive('getCachedCharacterData')->with($character, 'skill_reduction')->andReturn(0.0);
        $characterCacheData->shouldReceive('getCachedCharacterData')->with($character, 'resistance_reduction')->andReturn(0.0);

        $service = new RaidBattleService(
            $buildMonster,
            $characterCacheData,
            Mockery::mock(MonsterPlayerFight::class),
            Mockery::mock(BuildMonsterCacheService::class),
            Mockery::mock(BattleEventHandler::class),
        );

        $result = $service->setUpRaidBossBattle($character, $raidBoss);

        $this->assertSame(5, $result['attacks_left']);
    }

    public function testFirstAttackCreatesCurrentRaidParticipationWithoutChangingOldRaid(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16)->getCharacter();
        $monster = Monster::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'is_raid_boss' => true,
        ]);
        $oldLocation = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 32,
        ]);
        $oldRaid = Raid::factory()->create([
            'raid_boss_id' => $monster->id,
            'raid_boss_location_id' => $oldLocation->id,
        ]);
        $currentLocation = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 16,
            'y' => 16,
        ]);
        $currentRaid = Raid::factory()->create([
            'raid_boss_id' => $monster->id,
            'raid_boss_location_id' => $currentLocation->id,
        ]);
        $currentLocation->update([
            'raid_id' => $currentRaid->id,
        ]);
        RaidBoss::create([
            'raid_id' => $currentRaid->id,
            'raid_boss_id' => $monster->id,
            'boss_max_hp' => 100,
            'boss_current_hp' => 100,
            'raid_boss_deatils' => ['id' => $monster->id],
        ]);
        $oldParticipation = RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $oldRaid->id,
            'attacks_left' => 0,
            'damage_dealt' => 100,
            'killed_boss' => false,
        ]);

        Cache::put('raid-monsters', [
            $character->map->gameMap->name => [
                ['id' => $monster->id],
            ],
        ]);

        $serverMonster = Mockery::mock(ServerMonster::class);
        $serverMonster->shouldReceive('setHealth')->with(100)->once()->andReturnSelf();
        $serverMonster->shouldReceive('getMonster')->andReturn([
            'id' => $monster->id,
            'is_raid_boss' => true,
        ]);

        $fightDataServerMonster = Mockery::mock(ServerMonster::class);
        $fightDataServerMonster->shouldReceive('setMonster')->with(['id' => $monster->id])->once()->andReturnSelf();
        $fightDataServerMonster->shouldReceive('setHealth')->with(100)->once()->andReturnSelf();
        $this->instance(ServerMonster::class, $fightDataServerMonster);

        $buildMonster = Mockery::mock(BuildMonster::class);
        $buildMonster->shouldReceive('buildMonster')->once()->andReturn($serverMonster);
        $characterCacheData = Mockery::mock(CharacterCacheData::class);
        $characterCacheData->shouldReceive('getCachedCharacterData')->with($character, 'stat_affixes')->andReturn([]);
        $characterCacheData->shouldReceive('getCachedCharacterData')->with($character, 'skill_reduction')->andReturn(0.0);
        $characterCacheData->shouldReceive('getCachedCharacterData')->with($character, 'resistance_reduction')->andReturn(0.0);
        $monsterPlayerFight = Mockery::mock(MonsterPlayerFight::class);
        $monsterPlayerFight->shouldReceive('getBattleMessages')->andReturn([]);
        $monsterPlayerFight->shouldReceive('setUpRaidFight')->once()->andReturnSelf();
        $monsterPlayerFight->shouldReceive('fightSetUp')->once()->andReturn([
            'health' => [
                'current_character_health' => 10,
                'current_monster_health' => 100,
            ],
        ]);
        $monsterPlayerFight->shouldReceive('processAttack')->once()->andReturn(true);
        $monsterPlayerFight->shouldReceive('getCharacterHealth')->andReturn(10);
        $monsterPlayerFight->shouldReceive('getMonsterHealth')->andReturn(90);

        $service = new RaidBattleService(
            $buildMonster,
            $characterCacheData,
            $monsterPlayerFight,
            Mockery::mock(BuildMonsterCacheService::class),
            Mockery::mock(BattleEventHandler::class),
        );

        $service->setRaidBossHealth(100)->fightRaidMonster($character, $monster->id, 'attack', true);

        $currentParticipation = RaidBossParticipation::where('character_id', $character->id)
            ->where('raid_id', $currentRaid->id)
            ->first();

        $this->assertNotNull($currentParticipation);
        $this->assertSame(4, $currentParticipation->attacks_left);
        $this->assertSame(0, $oldParticipation->refresh()->attacks_left);
    }
}
