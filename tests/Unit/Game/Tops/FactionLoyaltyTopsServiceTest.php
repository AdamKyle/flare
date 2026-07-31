<?php

namespace Tests\Unit\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\Faction;
use App\Flare\Models\GameMap;
use App\Flare\Models\User;
use App\Game\Tops\Services\FactionLoyaltyTopsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactionLoyaltyTopsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_faction_loyalty_tops_ranks_by_faction_progression(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $map = GameMap::factory()->create();
        Faction::create(['character_id' => $character->id, 'game_map_id' => $map->id, 'current_level' => 8, 'current_points' => 100, 'points_needed' => 200, 'maxed' => false, 'title' => 'Known']);

        $data = $this->app->make(FactionLoyaltyTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(8, $data['rows'][0]['highest_faction_level']);
    }

    public function test_faction_loyalty_tops_includes_highest_faction_name(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $map = GameMap::factory()->create(['name' => 'Highest Faction Name Map']);
        Faction::create(['character_id' => $character->id, 'game_map_id' => $map->id, 'current_level' => 8, 'current_points' => 100, 'points_needed' => 200, 'maxed' => false, 'title' => 'Known']);

        $data = $this->app->make(FactionLoyaltyTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame('Highest Faction Name Map', $data['rows'][0]['highest_faction_name']);
    }

    public function test_faction_loyalty_tops_includes_total_faction_level_across_maps(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $mapOne = GameMap::factory()->create();
        $mapTwo = GameMap::factory()->create();
        Faction::create(['character_id' => $character->id, 'game_map_id' => $mapOne->id, 'current_level' => 5, 'current_points' => 0, 'points_needed' => 200, 'maxed' => false, 'title' => 'Known']);
        Faction::create(['character_id' => $character->id, 'game_map_id' => $mapTwo->id, 'current_level' => 3, 'current_points' => 0, 'points_needed' => 200, 'maxed' => false, 'title' => 'Known']);

        $data = $this->app->make(FactionLoyaltyTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(8, $data['rows'][0]['total_faction_level']);
    }

    public function test_faction_loyalty_current_month_and_all_time_use_cumulative_rows_and_primary_metrics(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $map = GameMap::factory()->create();
        Faction::create(['character_id' => $character->id, 'game_map_id' => $map->id, 'current_level' => 8, 'current_points' => 100, 'points_needed' => 200, 'maxed' => false, 'title' => 'Known']);

        $currentMonth = $this->app->make(FactionLoyaltyTopsService::class)->leaderboard(['period' => 'current_month']);
        $allTime = $this->app->make(FactionLoyaltyTopsService::class)->leaderboard(['period' => 'all_time']);

        $this->assertSame($character->id, $currentMonth['rows'][0]['character_id']);
        $this->assertSame($currentMonth['rows'][0]['highest_faction_level'], $allTime['rows'][0]['highest_faction_level']);
        $this->assertSame(['highest_faction_level', 'total_faction_level', 'npcs_helped_count', 'total_npc_fame_level'], collect($currentMonth['available_metrics'])->pluck('key')->all());
    }
}
