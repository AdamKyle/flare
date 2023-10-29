<?php

namespace Tests\Traits;

use App\Flare\Models\Event;

trait CreateEvent {

    public function createEvent(array $options = []): Event {
        return Event::factory()->create($options);
    }
}
