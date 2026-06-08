<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateUserLoginDuration;

class OnlineUsersControllerSecurityTest extends TestCase
{
    use CreateUserLoginDuration, RefreshDatabase;

    public function test_public_characters_online_payload_excludes_activity_details(): void
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
        $response->assertJsonFragment(['name' => $character->name]);
        $response->assertJsonMissing(['duration' => 600]);
        $response->assertJsonMissing(['currently_exploring' => false]);
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
