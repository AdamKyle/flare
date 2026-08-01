<?php

namespace Tests\Feature\Game\Tops;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;

class FactionLoyaltyTopsPageTest extends TestCase
{
    use CreateCharacter, CreateUser, RefreshDatabase;

    public function test_unauthenticated_users_cannot_view_faction_loyalty_tops(): void
    {
        $this->assertSame(302, $this->call('GET', '/game/tops/faction-loyalty')->getStatusCode());
    }

    public function test_authenticated_users_can_view_faction_loyalty_tops_mount(): void
    {
        $user = $this->createUser();
        $this->createCharacter(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/faction-loyalty');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="faction-loyalty-tops"', $response->getContent());
    }
}
