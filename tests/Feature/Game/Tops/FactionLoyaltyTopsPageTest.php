<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\User;
use App\Flare\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactionLoyaltyTopsPageTest extends TestCase
{
    use RefreshDatabase;

    public function testUnauthenticatedUsersCannotViewFactionLoyaltyTops(): void
    {
        $this->assertSame(302, $this->call('GET', '/game/tops/faction-loyalty')->getStatusCode());
    }

    public function testAuthenticatedUsersCanViewFactionLoyaltyTopsMount(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/faction-loyalty');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="faction-loyalty-tops"', $response->getContent());
    }
}
