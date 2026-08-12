<?php

use Tests\Traits\CreateUser;

uses(CreateUser::class);

beforeEach(function () {
    config(['broadcasting.default' => 'reverb']);

    require base_path('routes/game/messages/channels.php');
    require base_path('routes/game/automation/channels.php');
    require base_path('routes/game/maps/channels.php');
});

test('the authenticated owner is authorized for their private server message channel', function () {
    $user = $this->createUser();

    $response = $this->actingAs($user)->post('/broadcasting/auth', [
        'channel_name' => 'private-server-message-'.$user->id,
        'socket_id' => '1234.5678',
    ]);

    $response->assertOk();
});

test('a different authenticated user is rejected for another users private server message channel', function () {
    $owner = $this->createUser();
    $otherUser = $this->createUser();

    $response = $this->actingAs($otherUser)->post('/broadcasting/auth', [
        'channel_name' => 'private-server-message-'.$owner->id,
        'socket_id' => '1234.5678',
    ]);

    $response->assertForbidden();
});

test('the authenticated owner is authorized for their private exploration log channel', function () {
    $user = $this->createUser();

    $response = $this->actingAs($user)->post('/broadcasting/auth', [
        'channel_name' => 'private-automation-log-update-'.$user->id,
        'socket_id' => '1234.5678',
    ]);

    $response->assertOk();
});

test('a different authenticated user is rejected for another users private exploration log channel', function () {
    $owner = $this->createUser();
    $otherUser = $this->createUser();

    $response = $this->actingAs($otherUser)->post('/broadcasting/auth', [
        'channel_name' => 'private-automation-log-update-'.$owner->id,
        'socket_id' => '1234.5678',
    ]);

    $response->assertForbidden();
});

test('the authenticated owner is authorized for their private movement timeout channel', function () {
    $user = $this->createUser();

    $response = $this->actingAs($user)->post('/broadcasting/auth', [
        'channel_name' => 'private-show-timeout-move-'.$user->id,
        'socket_id' => '1234.5678',
    ]);

    $response->assertOk();
});

test('a different authenticated user is rejected for another users private movement timeout channel', function () {
    $owner = $this->createUser();
    $otherUser = $this->createUser();

    $response = $this->actingAs($otherUser)->post('/broadcasting/auth', [
        'channel_name' => 'private-show-timeout-move-'.$owner->id,
        'socket_id' => '1234.5678',
    ]);

    $response->assertForbidden();
});

test('the authenticated owner is authorized for their private delve status channel', function () {
    $user = $this->createUser();

    $response = $this->actingAs($user)->post('/broadcasting/auth', [
        'channel_name' => 'private-delve-status-updated-'.$user->id,
        'socket_id' => '1234.5678',
    ]);

    $response->assertOk();
});

test('a different authenticated user is rejected for another users private delve status channel', function () {
    $owner = $this->createUser();
    $otherUser = $this->createUser();

    $response = $this->actingAs($otherUser)->post('/broadcasting/auth', [
        'channel_name' => 'private-delve-status-updated-'.$owner->id,
        'socket_id' => '1234.5678',
    ]);

    $response->assertForbidden();
});

test('an unauthenticated request is rejected for a private user channel', function () {
    $user = $this->createUser();

    $response = $this->post('/broadcasting/auth', [
        'channel_name' => 'private-server-message-'.$user->id,
        'socket_id' => '1234.5678',
    ]);

    $response->assertForbidden();
});
