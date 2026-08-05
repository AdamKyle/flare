<?php

use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

uses(CreateRole::class, CreateUser::class);

test('forgot password page is shown', function () {
    $response = $this->get(route('password.request'));

    $response->assertSee('Reset Password');
});

test('admin user reset sends a link via the password broker', function () {
    $user = $this->createUser();

    $this->createAdminRole();
    $user->assignRole('Admin');

    $response = $this->post(route('password.email'), [
        'email' => $user->email,
    ]);

    $response->assertRedirect('/');
    $response->assertSessionHas('success', 'Sent you an email to begin the reset process.');
});

test('non admin user reset sends a custom reset password email', function () {
    $user = $this->createUser();

    $response = $this->post(route('password.email'), [
        'email' => $user->email,
    ]);

    $response->assertRedirect('/');
    $response->assertSessionHas('success', 'Sent you an email to begin the reset process.');
});

test('fails with unknown email', function () {
    $response = $this->post(route('password.email'), [
        'email' => 'unknown@example.com',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'This email does not match our records.');
});
