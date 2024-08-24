<?php

namespace Tests\Traits;

use App\Flare\Models\ScheduledEvent;

trait CreateScheduledEvent
{
    public function createScheduledEvent(array $options = []): ScheduledEvent
    {
        return ScheduledEvent::factory()->create($options);
    }
}
