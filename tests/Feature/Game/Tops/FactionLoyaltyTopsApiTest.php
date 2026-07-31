<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\Faction;
use App\Flare\Models\GameMap;
use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactionLoyaltyTopsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_call_faction_loyalty_tops_api(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Loyalist']);
        $map = GameMap::factory()->create();
        Faction::create(['character_id' => $character->id, 'game_map_id' => $map->id, 'current_level' => 7, 'current_points' => 100, 'points_needed' => 200, 'maxed' => false, 'title' => 'Known']);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/faction-loyalty');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(7, $data['rows'][0]['highest_faction_level']);
        $this->assertSame('/game/tops/characters/'.$character->id, $data['rows'][0]['character_profile_url']);
        $this->assertStringNotContainsString($user->email, $response->getContent());
    }
}
