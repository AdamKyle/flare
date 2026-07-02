<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterTopsApiTest extends TestCase
{
    use RefreshDatabase;

    public function testUnauthenticatedUsersCannotCallCharacterTopsApi(): void
    {
        $this->assertSame(302, $this->call('GET', '/api/game/tops/characters')->getStatusCode());
    }

    public function testAuthenticatedUsersCanCallCharacterTopsApiWithoutPrivateFields(): void
    {
        $user = User::factory()->create(['email' => 'private@example.com', 'password' => 'secret', 'remember_token' => 'token', 'ip_address' => '127.0.0.1']);
        Character::factory()->create(['user_id' => $user->id, 'name' => 'Public Hero', 'level' => 10]);
        UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('Public Hero', $data['rows'][0]['character_name']);
        $this->assertSame('/game/tops/characters/'.$data['rows'][0]['character_id'], $data['rows'][0]['character_profile_url']);
        $this->assertStringNotContainsString('private@example.com', $response->getContent());
        $this->assertStringNotContainsString('password', $response->getContent());
        $this->assertStringNotContainsString('remember_token', $response->getContent());
        $this->assertStringNotContainsString('ip_address', $response->getContent());
    }

    public function testCharacterProfileOverviewReturnsWhitelistedPublicFields(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Profile Hero']);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters/'.$character->id.'/overview');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('Profile Hero', $data['name']);
        $this->assertArrayNotHasKey('email', $data);
        $this->assertArrayNotHasKey('password', $data);
    }

    public function testCharacterProfileOverviewDoesNotExposeIp(): void
    {
        $user = User::factory()->create(['ip_address' => '10.0.0.1']);
        $character = Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters/'.$character->id.'/overview');

        $this->assertStringNotContainsString('10.0.0.1', $response->getContent());
        $this->assertStringNotContainsString('ip_address', $response->getContent());
    }

    public function testCharacterProfileOverviewDoesNotExposeRememberToken(): void
    {
        $user = User::factory()->create(['remember_token' => 'private-token']);
        $character = Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/characters/'.$character->id.'/overview');

        $this->assertStringNotContainsString('private-token', $response->getContent());
        $this->assertStringNotContainsString('remember_token', $response->getContent());
    }
}
