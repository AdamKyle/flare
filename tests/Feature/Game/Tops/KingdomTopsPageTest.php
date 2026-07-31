<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KingdomTopsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_cannot_view_kingdom_tops(): void
    {
        $this->assertSame(302, $this->call('GET', '/game/tops/kingdoms')->getStatusCode());
    }

    public function test_authenticated_users_can_view_kingdom_tops_mount(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/kingdoms');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="kingdom-tops"', $response->getContent());
    }
}
