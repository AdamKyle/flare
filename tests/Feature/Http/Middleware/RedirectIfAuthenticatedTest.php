<?php

use Tests\Traits\CreateUser;

uses(CreateUser::class);

test('authenticated user visiting a guest only route is redirected home', function () {
    $user = $this->createUser();

    $response = $this->actingAs($user)->get('/login');

    $response->assertRedirect('/home');
});

test('guest visiting a guest only route passes through', function () {
    $response = $this->get('/login');

    $response->assertOk();
});
