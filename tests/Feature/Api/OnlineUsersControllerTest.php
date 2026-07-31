<?php

namespace Tests\Feature\Api;

use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class OnlineUsersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_characters_online_endpoint_returns_useful_character_details(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['name' => 'Api Online Character', 'level' => 33]);
        UserLoginDuration::factory()->create([
            'user_id' => $character->user_id,
            'logged_in_at' => now()->subMinutes(5),
            'last_activity' => now()->subMinute(),
            'last_heart_beat' => now()->subMinute(),
            'duration_in_seconds' => null,
        ]);

        $response = $this->call('GET', '/api/characters-online');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('Api Online Character', $data['characters_online'][0]['name']);
        $this->assertSame(33, $data['characters_online'][0]['level']);
        $this->assertSame('Surface', $data['characters_online'][0]['map']);
        $this->assertArrayHasKey('duration', $data['characters_online'][0]);
        $this->assertArrayHasKey('currently_exploring', $data['characters_online'][0]);
        $this->assertArrayHasKey('last_activity', $data['characters_online'][0]);
        $this->assertArrayHasKey('last_heart_beat', $data['characters_online'][0]);
        $this->assertArrayNotHasKey('user_id', $data['characters_online'][0]);
        $this->assertArrayNotHasKey('email', $data['characters_online'][0]);
    }

    public function test_whos_playing_statistics_endpoint_returns_public_summaries_without_emails(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->user->update(['email' => 'private@example.com']);
        User::factory()->create(['created_at' => now()->subMinutes(30)]);
        User::factory()->create(['created_at' => now()->subDays(20)]);
        UserLoginDuration::factory()->create([
            'user_id' => $character->user_id,
            'logged_in_at' => now()->subMinutes(10),
            'last_activity' => now()->subMinute(),
            'last_heart_beat' => now()->subMinute(),
            'duration_in_seconds' => null,
        ]);

        $response = $this->call('GET', '/api/whos-playing-statistics');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertArrayHasKey('signup_summary', $data);
        $this->assertArrayHasKey('login_summary', $data);
        $this->assertArrayHasKey('today', $data['signup_summary']);
        $this->assertArrayHasKey('last_hour', $data['signup_summary']);
        $this->assertArrayHasKey('last_month', $data['signup_summary']);
        $this->assertArrayHasKey('last_year', $data['signup_summary']);
        $this->assertArrayHasKey('today', $data['login_summary']);
        $this->assertArrayHasKey('last_hour', $data['login_summary']);
        $this->assertArrayHasKey('last_month', $data['login_summary']);
        $this->assertArrayHasKey('last_year', $data['login_summary']);
        $this->assertArrayNotHasKey('email', $data['characters_online'][0]);
        $this->assertStringNotContainsString('private@example.com', $response->getContent());
    }
}
