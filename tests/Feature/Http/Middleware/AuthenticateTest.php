<?php

use Tests\Traits\CreateUser;

uses(CreateUser::class);

test('guest requesting an html page is redirected to login', function () {
    $user = $this->createUser();

    $response = $this->post(route('delete.account', $user));

    $response->assertRedirect(route('login'));
});

test('guest requesting json from a protected route receives a 401', function () {
    $user = $this->createUser();

    $response = $this->postJson(route('delete.account', $user));

    $response->assertStatus(401);
    $response->assertJson(['message' => 'Unauthenticated.']);
});
