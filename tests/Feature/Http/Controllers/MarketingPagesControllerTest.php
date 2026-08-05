<?php

test('features page renders', function () {
    $response = $this->get(route('game.features'));

    $response->assertOk();
});

test('whos playing page renders', function () {
    $response = $this->get(route('game.whos-playing'));

    $response->assertOk();
});
