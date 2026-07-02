<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\User;
use App\Flare\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExplorationTopsPageTest extends TestCase
{
    use RefreshDatabase;

    public function testUnauthenticatedUsersCannotViewExplorationTops(): void
    {
        $this->assertSame(302, $this->call('GET', '/game/tops/exploration')->getStatusCode());
    }

    public function testAuthenticatedUsersCanViewExplorationTopsMount(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/exploration');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="exploration-tops"', $response->getContent());
    }
}
