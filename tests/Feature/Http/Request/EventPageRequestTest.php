<?php

test('event type is required with a custom message', function () {
    $response = $this->get(route('event.type'));

    $response->assertSessionHasErrors(['event_type' => 'Event type is required.']);
});

test('a request with a valid event type passes validation', function () {
    $response = $this->get(route('event.type', ['event_type' => 'ice-queen-raid']));

    $response->assertSessionHasNoErrors();
});
