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

    public function testFactionLoyaltyTopsRanksByFactionProgression(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $map = GameMap::factory()->create();
        Faction::create(['character_id' => $character->id, 'game_map_id' => $map->id, 'current_level' => 8, 'current_points' => 100, 'points_needed' => 200, 'maxed' => false, 'title' => 'Known']);

        $data = $this->app->make(FactionLoyaltyTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(8, $data['rows'][0]['highest_faction_level']);
    }
}
