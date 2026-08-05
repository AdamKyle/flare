<?php

use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateUser;

uses(CreateGuideQuest::class, CreateUser::class);

test('login password input uses current password autocomplete', function () {
    $response = $this->get('/login');

    $response->assertSee('autocomplete="current-password"', false);
    $response->assertDontSee('autocomplete="new-password"', false);
});

test('existing user can login with correct password', function () {
    $user = $this->createUser([
        'email' => 'login-test@example.com',
    ]);

    $response = $this->post(route('login'), [
        'email' => 'login-test@example.com',
        'password' => 'ReallyLongPassword',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});

test('user can login when guide quest requires delve pack size without delve data', function () {
    $this->createGuideQuest([
        'required_delve_pack_size' => 5,
    ]);

    $character = (new CharacterFactory)
        ->setAttributesOnUserForCreation([
            'email' => 'delve-login-test@example.com',
            'guide_enabled' => true,
        ])
        ->createBaseCharacter()
        ->givePlayerLocation()
        ->getCharacter();

    $response = $this->post(route('login'), [
        'email' => 'delve-login-test@example.com',
        'password' => 'ReallyLongPassword',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($character->user);
});

test('user without character does not have deletion flag cleared on login', function () {
    $user = $this->createUser([
        'email' => 'login-test@example.com',
        'will_be_deleted' => true,
    ]);

    $response = $this->post(route('login'), [
        'email' => 'login-test@example.com',
        'password' => 'ReallyLongPassword',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
    $this->assertTrue($user->refresh()->will_be_deleted);
});

test('user with character without inventory is logged out and retains deletion flag', function () {
    $character = (new CharacterFactory)
        ->setAttributesOnUserForCreation([
            'email' => 'missing-inventory-login-test@example.com',
            'will_be_deleted' => false,
        ])
        ->createBaseCharacter()
        ->getCharacter();
    $character->inventory()->delete();

    $response = $this->post(route('login'), [
        'email' => 'missing-inventory-login-test@example.com',
        'password' => 'ReallyLongPassword',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHas('error', 'Your previous character no longer exists. Please create a new character.');
    $this->assertGuest();
    $this->assertTrue($character->user->refresh()->will_be_deleted);
});

test('existing user cannot login with wrong password', function () {
    $this->createUser([
        'email' => 'login-test@example.com',
    ]);

    $response = $this->post(route('login'), [
        'email' => 'login-test@example.com',
        'password' => 'WrongPassword',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('login is rejected while registration and login is disabled for an unrecognized user', function () {
    config(['app.disabled_reg_and_login' => true]);

    $response = $this->post(route('login'), [
        'email' => 'unrecognized-user@example.com',
        'password' => 'WhateverPassword',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'I am sorry, right now the Registration and Login has been disabled while server maintenance and stability testing is taking place. We hope to be back up and running soon!');
    $this->assertGuest();
});

test('login is locked out after too many failed attempts', function () {
    $this->createUser([
        'email' => 'lockout-test@example.com',
    ]);

    $this->post(route('login'), ['email' => 'lockout-test@example.com', 'password' => 'WrongPassword']);
    $this->post(route('login'), ['email' => 'lockout-test@example.com', 'password' => 'WrongPassword']);
    $this->post(route('login'), ['email' => 'lockout-test@example.com', 'password' => 'WrongPassword']);
    $this->post(route('login'), ['email' => 'lockout-test@example.com', 'password' => 'WrongPassword']);
    $this->post(route('login'), ['email' => 'lockout-test@example.com', 'password' => 'WrongPassword']);

    $response = $this->post(route('login'), ['email' => 'lockout-test@example.com', 'password' => 'WrongPassword']);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('Too many login attempts');
});
