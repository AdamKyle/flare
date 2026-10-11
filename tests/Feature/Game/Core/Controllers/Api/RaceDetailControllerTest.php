<?php

namespace Tests\Feature\Game\Core\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class RaceDetailControllerTest extends TestCase
{
    use CreateRace, CreateUser, RefreshDatabase;

    public function test_authenticated_player_can_fetch_race_detail(): void
    {
        $user = $this->createUser();
        $race = $this->createRace([
            'name' => 'Human',
            'description' => 'Adaptable and determined.',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/game-data/races/'.$race->id);

        $response
            ->assertOk()
            ->assertJsonPath('id', $race->id)
            ->assertJsonPath('name', 'Human')
            ->assertJsonPath('description', 'Adaptable and determined.')
            ->assertJsonStructure(['image_url']);
    }
}
