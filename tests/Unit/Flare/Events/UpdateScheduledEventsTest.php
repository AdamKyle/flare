<?php

namespace Tests\Unit\Flare\Events;

use App\Flare\Events\UpdateScheduledEvents;
use Illuminate\Broadcasting\PresenceChannel;
use Tests\TestCase;

class UpdateScheduledEventsTest extends TestCase
{
    public function test_broadcast_on_returns_the_update_event_schedule_presence_channel(): void
    {
        $event = new UpdateScheduledEvents([]);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PresenceChannel::class, $channel);
        $this->assertSame('presence-update-event-schedule', $channel->name);
    }
}
