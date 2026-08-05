<?php

use Tests\Traits\CreateUser;

uses(CreateUser::class);

test('guest is redirected to login when visiting the confirm password page', function () {
    $response = $this->get(route('password.confirm'));

    $response->assertRedirect(route('login'));
});

test('confirming with the correct password redirects to the intended path', function () {
    $user = $this->createUser();

    $response = $this->actingAs($user)->post('/password/confirm', [
        'password' => 'ReallyLongPassword',
    ]);

    $response->assertRedirect('/home');
    $this->assertNotNull(session('auth.password_confirmed_at'));
});

test('confirming with the wrong password fails validation', function () {
    $user = $this->createUser();

    $response = $this->actingAs($user)->post('/password/confirm', [
        'password' => 'TheWrongPassword',
    ]);

    $response->assertSessionHasErrors('password');
});
