<?php

namespace Tests\Unit\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use App\Game\Tops\Services\KingdomTopsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KingdomTopsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_kingdom_tops_excludes_npc_owned_kingdoms(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $map = GameMap::factory()->create();
        Kingdom::factory()->create(['character_id' => $character->id, 'game_map_id' => $map->id, 'npc_owned' => false, 'treasury' => 100]);
        Kingdom::factory()->create(['character_id' => $character->id, 'game_map_id' => $map->id, 'npc_owned' => true, 'treasury' => 500]);

        $data = $this->app->make(KingdomTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(1, $data['rows'][0]['kingdom_count']);
    }

    public function test_kingdom_tops_defaults_to_ranking_by_kingdom_count(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $map = GameMap::factory()->create();
        Kingdom::factory()->create(['character_id' => $character->id, 'game_map_id' => $map->id, 'npc_owned' => false, 'treasury' => 100]);

        $data = $this->app->make(KingdomTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame('kingdom_count', $data['metric']);
    }
}
