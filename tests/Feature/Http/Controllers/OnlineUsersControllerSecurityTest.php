<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateUserLoginDuration;

class OnlineUsersControllerSecurityTest extends TestCase
{
    use CreateUserLoginDuration, RefreshDatabase;

    public function test_public_characters_online_payload_excludes_account_details_but_includes_activity_details(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createUserLoginDuration([
            'user_id' => $character->user_id,
            'logged_in_at' => now()->subMinutes(10),
            'last_activity' => now(),
            'last_heart_beat' => now(),
        ]);

        $response = $this->call('GET', '/api/characters-online');

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => $character->name,
            'level' => $character->level,
            'map' => 'Surface',
        ]);
        $response->assertJsonPath('characters_online.0.duration', fn ($duration) => is_int($duration));
        $response->assertJsonPath('characters_online.0.currently_exploring', fn ($currentlyExploring) => is_bool($currentlyExploring));
        $response->assertJsonPath('characters_online.0.last_activity', fn ($lastActivity) => is_string($lastActivity));
        $response->assertJsonPath('characters_online.0.last_heart_beat', fn ($lastHeartBeat) => is_string($lastHeartBeat));
        $response->assertJsonMissingPath('characters_online.0.user_id');
        $response->assertJsonMissingPath('characters_online.0.email');
        $response->assertJsonMissing(['user_id' => $character->user_id]);
        $response->assertJsonMissing(['email' => $character->user->email]);
    }

    public function test_public_aggregate_activity_endpoints_exclude_account_data(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        foreach ([
            '/api/user-login-duration?daysPast=0',
            '/api/character-logins?daysPast=0',
            '/api/character-registrations?daysPast=0',
        ] as $endpoint) {
            $response = $this->call('GET', $endpoint);

            $response->assertOk();
            $response->assertJsonMissing(['user_id' => $character->user_id]);
            $response->assertJsonMissing(['email' => $character->user->email]);
        }
    }
}
