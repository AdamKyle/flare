<?php

use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateUserLoginDuration;

uses(CreateUserLoginDuration::class);

test('public characters online payload excludes account details but includes activity details', function () {
    $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
    $this->createUserLoginDuration([
        'user_id' => $character->user_id,
        'logged_in_at' => now()->subMinutes(10),
        'last_activity' => now(),
        'last_heart_beat' => now(),
    ]);

    $response = $this->get('/api/characters-online');

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
});

test('public aggregate activity endpoints exclude account data', function () {
    $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

    foreach ([
        '/api/user-login-duration?daysPast=0',
        '/api/character-logins?daysPast=0',
        '/api/character-registrations?daysPast=0',
    ] as $endpoint) {
        $response = $this->get($endpoint);

        $response->assertOk();
        $response->assertJsonMissing(['user_id' => $character->user_id]);
        $response->assertJsonMissing(['email' => $character->user->email]);
    }
});

test('whos playing statistics snapshot returns the expected top level sections', function () {
    $response = $this->get('/api/whos-playing-statistics');

    $response->assertOk();
    $response->assertJsonStructure([
        'characters_online',
        'signup_summary' => ['today', 'last_hour', 'last_month', 'last_year'],
        'login_summary' => ['today', 'last_hour', 'last_month', 'last_year'],
        'login_duration_chart' => ['labels', 'data'],
        'login_chart' => ['labels', 'data'],
        'registration_chart' => ['labels', 'data'],
    ]);
});

test('character logins endpoint rejects an invalid days past filter', function () {
    $response = $this->getJson('/api/character-logins?daysPast=99');

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('daysPast');
});
