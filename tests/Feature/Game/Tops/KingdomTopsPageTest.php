<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\User;
use App\Flare\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KingdomTopsPageTest extends TestCase
{
    use RefreshDatabase;

    public function testUnauthenticatedUsersCannotViewKingdomTops(): void
    {
        $this->assertSame(302, $this->call('GET', '/game/tops/kingdoms')->getStatusCode());
    }

    public function testAuthenticatedUsersCanViewKingdomTopsMount(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/kingdoms');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="kingdom-tops"', $response->getContent());
    }
}
