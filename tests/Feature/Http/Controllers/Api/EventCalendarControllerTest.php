<?php

use Tests\Traits\CreateScheduledEvent;

uses(CreateScheduledEvent::class);

test('load events returns scheduled events for the calendar', function () {
    $event = $this->createScheduledEvent();

    $response = $this->get('/api/calendar/fetch-upcoming-events');

    $response->assertOk();
    $response->assertJsonPath('events.0.event_id', $event->id);
    $response->assertJsonStructure([
        'events' => [
            ['event_id', 'title', 'start', 'end', 'description'],
        ],
    ]);
});

test('load events returns an empty list when no scheduled events exist', function () {
    $response = $this->get('/api/calendar/fetch-upcoming-events');

    $response->assertOk();
    $response->assertExactJson(['events' => []]);
});
