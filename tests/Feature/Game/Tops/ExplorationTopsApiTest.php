<?php

namespace Tests\Feature\Game\Tops;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateExplorationLog;
use Tests\Traits\CreateUser;

class ExplorationTopsApiTest extends TestCase
{
    use CreateCharacter, CreateExplorationLog, CreateUser, RefreshDatabase;

    public function test_authenticated_users_can_call_exploration_tops_api(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'name' => 'Explorer']);
        $this->createExplorationLog(['character_id' => $character->id, 'user_id' => $user->id, 'kills' => 5, 'started_at' => now()]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/exploration');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(5, $data['rows'][0]['kills']);
        $this->assertSame('/game/tops/characters/'.$character->id, $data['rows'][0]['character_profile_url']);
        $this->assertStringNotContainsString($user->email, $response->getContent());
    }
}
