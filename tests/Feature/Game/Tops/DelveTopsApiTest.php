<?php

namespace Tests\Feature\Game\Tops;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateDelveExploration;
use Tests\Traits\CreateUser;

class DelveTopsApiTest extends TestCase
{
    use CreateCharacter, CreateDelveExploration, CreateUser, RefreshDatabase;

    public function test_authenticated_users_can_call_delve_tops_api(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'name' => 'Delver']);
        $this->createDelveExploration(['character_id' => $character->id, 'increase_enemy_strength' => 2.5, 'started_at' => now()]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/delve');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(2.5, $data['rows'][0]['strongest_enemy_increase']);
        $this->assertSame('/game/tops/characters/'.$character->id, $data['rows'][0]['character_profile_url']);
        $this->assertStringNotContainsString($user->email, $response->getContent());
    }
}
