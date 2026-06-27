<?php

namespace Tests\Feature\Game\Battle\Controllers\Api;

use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Raid;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\RaidBossParticipation;
use App\Game\Battle\Controllers\Api\RaidBattleController;
use App\Game\Battle\Request\AttackTypeRequest;
use App\Game\Battle\Services\RaidBattleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class RaidBattleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_exhausted_participation_for_another_raid_does_not_block_current_raid_attack(): void
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
        RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $currentRaid->id,
            'raid_boss_id' => RaidBoss::create([
                'raid_id' => $currentRaid->id,
                'raid_boss_id' => $monster->id,
                'boss_max_hp' => 100,
                'boss_current_hp' => 100,
            ])->id,
            'attacks_left' => 0,
            'damage_dealt' => 100,
            'killed_boss' => false,
        ]);

        $raidBattleService = Mockery::mock(RaidBattleService::class);
        $raidBattleService->shouldReceive('setRaidBossHealth')
            ->once()
            ->with(100)
            ->andReturnSelf();
        $raidBattleService->shouldReceive('fightRaidMonster')
            ->once()
            ->with($character, $monster->id, 'attack', true)
            ->andReturn([
                'status' => 200,
                'monster_current_health' => 90,
            ]);

        $request = new AttackTypeRequest;
        $request->merge([
            'attack_type' => 'attack',
        ]);

        $response = (new RaidBattleController($raidBattleService))->fightMonster($request, $character, $monster);

        $this->assertSame(200, $response->getStatusCode());
    }
}
