<?php

use Tests\Traits\CreateUser;

uses(CreateUser::class);

beforeEach(function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-reverb-key',
        'broadcasting.connections.reverb.secret' => 'test-reverb-secret',
        'broadcasting.connections.reverb.app_id' => 'test-reverb-app-id',
        'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        'broadcasting.connections.reverb.options.port' => 8080,
        'broadcasting.connections.reverb.options.scheme' => 'http',
        'broadcasting.connections.reverb.options.useTLS' => false,
    ]);

    require base_path('routes/game/messages/channels.php');
    require base_path('routes/game/automation/channels.php');
    require base_path('routes/game/maps/channels.php');
    require base_path('routes/game/channels.php');

    require base_path('routes/game/automation/delve/channels.php');
    require base_path('routes/game/automation/batch-crafting/channels.php');
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

test('the authenticated owner is authorized for their private batch crafting status channel', function () {
    $user = $this->createUser();

    $response = $this->actingAs($user)->post('/broadcasting/auth', [
        'channel_name' => 'private-batch-crafting-status-updated-'.$user->id,
        'socket_id' => '1234.5678',
    ]);

    $response->assertOk();
});

test('a different authenticated user is rejected for another users private batch crafting status channel', function () {
    $owner = $this->createUser();
    $otherUser = $this->createUser();

    $response = $this->actingAs($otherUser)->post('/broadcasting/auth', [
        'channel_name' => 'private-batch-crafting-status-updated-'.$owner->id,
        'socket_id' => '1234.5678',
    ]);

    $response->assertForbidden();
});

test('the authenticated owner is authorized for their private gem progression channel', function () {
    $user = $this->createUser();

    $response = $this->actingAs($user)->post('/broadcasting/auth', [
        'channel_name' => 'private-update-gem-progression-'.$user->id,
        'socket_id' => '1234.5678',
    ]);

    $response->assertOk();
});

test('a different authenticated user is rejected for another users private gem progression channel', function () {
    $owner = $this->createUser();
    $otherUser = $this->createUser();

    $response = $this->actingAs($otherUser)->post('/broadcasting/auth', [
        'channel_name' => 'private-update-gem-progression-'.$owner->id,
        'socket_id' => '1234.5678',
    ]);

    $response->assertForbidden();
});

test('any authenticated user is authorized for the shared gem profile progression channel', function () {
    $user = $this->createUser();

    $response = $this->actingAs($user)->post('/broadcasting/auth', [
        'channel_name' => 'private-gem-profile-progression-map_gem-1',
        'socket_id' => '1234.5678',
    ]);

    $response->assertOk();
});

test('an unauthenticated request is rejected for the shared gem profile progression channel', function () {
    $response = $this->post('/broadcasting/auth', [
        'channel_name' => 'private-gem-profile-progression-map_gem-1',
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
