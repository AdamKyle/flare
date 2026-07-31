<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\User;
use App\Flare\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DelveTopsPageTest extends TestCase
{
    use RefreshDatabase;

    public function testUnauthenticatedUsersCannotViewDelveTops(): void
    {
        $this->assertSame(302, $this->call('GET', '/game/tops/delve')->getStatusCode());
    }

    public function testAuthenticatedUsersCanViewDelveTopsMount(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/delve');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="delve-tops"', $response->getContent());
    }
}
