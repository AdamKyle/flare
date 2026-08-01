<?php

namespace Tests\Feature\Game\Tops;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;

class KingdomTopsPageTest extends TestCase
{
    use CreateCharacter, CreateUser, RefreshDatabase;

    public function test_unauthenticated_users_cannot_view_kingdom_tops(): void
    {
        $this->assertSame(302, $this->call('GET', '/game/tops/kingdoms')->getStatusCode());
    }

    public function test_authenticated_users_can_view_kingdom_tops_mount(): void
    {
        $user = $this->createUser();
        $this->createCharacter(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/kingdoms');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="kingdom-tops"', $response->getContent());
    }
}
