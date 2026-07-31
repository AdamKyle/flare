<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KingdomTopsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_kingdom_tops_excludes_npc_owned_kingdoms_by_default(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Ruler']);
        $map = GameMap::factory()->create();
        Kingdom::factory()->create(['character_id' => $character->id, 'game_map_id' => $map->id, 'npc_owned' => false, 'treasury' => 100]);
        Kingdom::factory()->create(['character_id' => $character->id, 'game_map_id' => $map->id, 'npc_owned' => true, 'treasury' => 999999]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/kingdoms');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(1, $data['rows'][0]['kingdom_count']);
        $this->assertSame('/game/tops/characters/'.$character->id, $data['rows'][0]['character_profile_url']);
        $this->assertStringNotContainsString($user->email, $response->getContent());
    }
}
