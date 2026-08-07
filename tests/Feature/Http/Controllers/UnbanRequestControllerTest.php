<?php

use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

uses(CreateRole::class, CreateUser::class);

test('unban request page renders', function () {
    $response = $this->get(route('un.ban.request'));

    $response->assertOk();
});

test('request form shows the form for a token that exists in the cache', function () {
    $token = 'a-valid-token';
    Cache::put('unban-request-token-'.$token, 0, now()->addMinutes(60));

    $response = $this->get(route('un.ban.request.form', $token));

    $response->assertOk();
    $response->assertSee($token);
});

test('request form redirects when the token is missing from the cache', function () {
    $response = $this->get(route('un.ban.request.form', 'missing-token'));

    $response->assertRedirect(route('un.ban.request'));
    $response->assertSessionHas('error', 'Unable to submit that request.');
});

test('public email lookup does not reveal account or ban state', function () {
    $user = $this->createUser();
    $tempBannedUser = (new CharacterFactory)->createBaseCharacter(
        assignBaseSkill: false,
        assignPassiveSkills: false,
        createClassRanks: false,
    )->banCharacter('Reason', null, now()->addDay())->getCharacter()->user;
    $permanentlyBannedUser = (new CharacterFactory)->createBaseCharacter(
        assignBaseSkill: false,
        assignPassiveSkills: false,
        createClassRanks: false,
    )->banCharacter('Reason')->getCharacter()->user;

    $responses = [
        $this->post(route('un.ban.request.email'), ['email' => 'unknown@example.com']),
        $this->post(route('un.ban.request.email'), ['email' => $user->email]),
        $this->post(route('un.ban.request.email'), ['email' => $tempBannedUser->email]),
        $this->post(route('un.ban.request.email'), ['email' => $permanentlyBannedUser->email]),
    ];

    foreach ($responses as $response) {
        $response->assertRedirect();
        $response->assertSessionHas('success', 'If this account is eligible, you may continue with an unban request.');
        $this->assertSame($responses[0]->getStatusCode(), $response->getStatusCode());
        $this->assertSame($responses[0]->headers->get('Location'), $response->headers->get('Location'));
    }
});

test('submit rejects missing or invalid find user token', function () {
    $user = (new CharacterFactory)->createBaseCharacter(
        assignBaseSkill: false,
        assignPassiveSkills: false,
        createClassRanks: false,
    )->banCharacter('Reason')->getCharacter()->user;

    $missingTokenResponse = $this->post(route('un.ban.request.submit'), [
        'unban_message' => 'Please review.',
    ]);
    $invalidTokenResponse = $this->post(route('un.ban.request.submit'), [
        'unban_message' => 'Please review.',
        'token' => 'invalid',
    ]);

    $missingTokenResponse->assertSessionHas('error', 'Unable to submit that request.');
    $invalidTokenResponse->assertSessionHas('error', 'Unable to submit that request.');
    $this->assertNull($user->refresh()->un_ban_request);
});

test('submit accepts a valid one time find user token', function () {
    Role::create(['name' => 'Admin']);

    $user = (new CharacterFactory)->createBaseCharacter(
        assignBaseSkill: false,
        assignPassiveSkills: false,
        createClassRanks: false,
    )->banCharacter('Reason')->getCharacter()->user;

    $lookupResponse = $this->post(route('un.ban.request.email'), ['email' => $user->email]);
    $lookupResponse->assertRedirect(route('un.ban.request'));
    $lookupResponse->assertSessionHas('unban_request_token');
    $token = session('unban_request_token');
    $response = $this->post(route('un.ban.request.submit'), [
        'unban_message' => 'Please review.',
        'token' => $token,
    ]);

    $response->assertSessionHas('success', 'Request submitted. We will contact you in the next 72 hours.');
    $this->assertSame('Please review.', $user->refresh()->un_ban_request);
});

test('valid find user token can only be used once', function () {
    Role::create(['name' => 'Admin']);

    $user = (new CharacterFactory)->createBaseCharacter(
        assignBaseSkill: false,
        assignPassiveSkills: false,
        createClassRanks: false,
    )->banCharacter('Reason')->getCharacter()->user;

    $lookupResponse = $this->post(route('un.ban.request.email'), ['email' => $user->email]);
    $lookupResponse->assertRedirect(route('un.ban.request'));
    $lookupResponse->assertSessionHas('unban_request_token');
    $token = session('unban_request_token');
    $this->post(route('un.ban.request.submit'), [
        'unban_message' => 'Please review.',
        'token' => $token,
    ]);

    $response = $this->post(route('un.ban.request.submit'), [
        'unban_message' => 'Second review.',
        'token' => $token,
    ]);

    $response->assertSessionHas('error', 'Unable to submit that request.');
    $this->assertSame('Please review.', $user->refresh()->un_ban_request);
});

test('submit notifies an existing admin user by real mail when eligible', function () {
    $adminRole = $this->createAdminRole();
    $adminUser = $this->createAdmin($adminRole);

    $user = (new CharacterFactory)->createBaseCharacter(
        assignBaseSkill: false,
        assignPassiveSkills: false,
        createClassRanks: false,
    )->banCharacter('Reason')->getCharacter()->user;

    $lookupResponse = $this->post(route('un.ban.request.email'), ['email' => $user->email]);
    $token = session('unban_request_token');

    $response = $this->post(route('un.ban.request.submit'), [
        'unban_message' => 'Please review my ban.',
        'token' => $token,
    ]);

    $response->assertSessionHas('success', 'Request submitted. We will contact you in the next 72 hours.');
    $this->assertSame('Please review my ban.', $user->refresh()->un_ban_request);
});

test('issued ineligible token does not reveal account state', function () {
    $lookupResponse = $this->post(route('un.ban.request.email'), ['email' => 'unknown@example.com']);
    $lookupResponse->assertRedirect(route('un.ban.request'));
    $lookupResponse->assertSessionHas('unban_request_token');
    $token = session('unban_request_token');

    $response = $this->post(route('un.ban.request.submit'), [
        'unban_message' => 'Please review.',
        'token' => $token,
    ]);

    $response->assertSessionHas('success', 'Request submitted. We will contact you in the next 72 hours.');
});
