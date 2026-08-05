<?php

use Illuminate\Support\Facades\Hash;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

uses(CreateRole::class, CreateUser::class);

test('reset password page is shown for a valid token', function () {
    $user = $this->createUser();

    $this->createAdminRole();
    $user->assignRole('Admin');

    $token = app('Password')::getRepository()->create($user);

    $response = $this->get(route('password.reset', $token));

    $response->assertSee('Reset Password');
});

test('reset fails with an email that does not match any user', function () {
    $response = $this->post(route('password.update'), [
        'token' => 'some-token',
        'email' => 'unknown@example.com',
        'password' => 'ANewLongPassword',
        'password_confirmation' => 'ANewLongPassword',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'This email does not match our records.');
});

test('reset succeeds with a valid token and updates the password', function () {
    $user = $this->createUser();
    $token = app('Password')::getRepository()->create($user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'ANewLongPassword',
        'password_confirmation' => 'ANewLongPassword',
    ]);

    $response->assertRedirect('/');
    $this->assertTrue(Hash::check('ANewLongPassword', $user->refresh()->password));
});

test('reset fails and redirects back with an invalid token', function () {
    $user = $this->createUser();

    $response = $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'ANewLongPassword',
        'password_confirmation' => 'ANewLongPassword',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertFalse(Hash::check('ANewLongPassword', $user->refresh()->password));
});
