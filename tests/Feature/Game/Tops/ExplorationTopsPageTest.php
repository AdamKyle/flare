<?php

namespace Tests\Feature\Game\Tops;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;

class ExplorationTopsPageTest extends TestCase
{
    use CreateCharacter, CreateUser, RefreshDatabase;

    public function test_unauthenticated_users_cannot_view_exploration_tops(): void
    {
        $this->assertSame(302, $this->call('GET', '/game/tops/exploration')->getStatusCode());
    }

    public function test_authenticated_users_can_view_exploration_tops_mount(): void
    {
        $user = $this->createUser();
        $this->createCharacter(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/exploration');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="exploration-tops"', $response->getContent());
    }
}
