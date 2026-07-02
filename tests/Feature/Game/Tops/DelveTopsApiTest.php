<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DelveTopsApiTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthenticatedUsersCanCallDelveTopsApi(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Delver']);
        DelveExploration::factory()->create(['character_id' => $character->id, 'increase_enemy_strength' => 2.5, 'started_at' => now()]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/delve');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(2.5, $data['rows'][0]['strongest_enemy_increase']);
        $this->assertSame('/game/tops/characters/'.$character->id, $data['rows'][0]['character_profile_url']);
        $this->assertStringNotContainsString($user->email, $response->getContent());
    }
}
