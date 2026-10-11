<?php

namespace Tests\Feature\Game\Core\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateUser;

class ClassDetailControllerTest extends TestCase
{
    use CreateClass, CreateUser, RefreshDatabase;

    public function test_authenticated_player_can_fetch_class_detail_with_prerequisites(): void
    {
        $user = $this->createUser();
        $fighter = $this->createClass(['name' => 'Fighter']);
        $thief = $this->createClass(['name' => 'Thief']);
        $buccaneer = $this->createClass([
            'name' => 'Buccaneer',
            'primary_required_class_id' => $fighter->id,
            'secondary_required_class_id' => $thief->id,
            'primary_required_class_level' => 10,
            'secondary_required_class_level' => 10,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/game-data/classes/'.$buccaneer->id);

        $response
            ->assertOk()
            ->assertJsonPath('id', $buccaneer->id)
            ->assertJsonPath('name', 'Buccaneer')
            ->assertJsonPath('unlock_requirements.primary_required_class.id', $fighter->id)
            ->assertJsonPath('unlock_requirements.secondary_required_class.id', $thief->id)
            ->assertJsonPath('unlock_requirements.primary_required_class_level', 10)
            ->assertJsonPath('unlock_requirements.secondary_required_class_level', 10)
            ->assertJsonStructure([
                'description',
                'damage_stat',
                'to_hit_stat',
                'attributes',
                'combat_modifiers',
            ]);
    }
}
