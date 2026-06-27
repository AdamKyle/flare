<?php

namespace Tests\Unit\Admin\Events;

use App\Admin\Events\FactionLoyaltyMonitoringUpdated;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Tests\TestCase;

class FactionLoyaltyMonitoringUpdatedTest extends TestCase
{
    public function test_event_implements_should_broadcast_for_async_dispatch(): void
    {
        $event = new FactionLoyaltyMonitoringUpdated(1);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
    }

    public function test_event_does_not_implement_should_broadcast_now(): void
    {
        $event = new FactionLoyaltyMonitoringUpdated(1);

        $this->assertNotInstanceOf(ShouldBroadcastNow::class, $event);
    }

    public function test_event_broadcasts_on_admin_monitoring_queue(): void
    {
        $event = new FactionLoyaltyMonitoringUpdated(1);

        $this->assertSame('admin_monitoring', $event->broadcastQueue());
    }

    public function test_event_broadcasts_on_correct_channel(): void
    {
        $event = new FactionLoyaltyMonitoringUpdated(1);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-admin-monitoring-faction-loyalty', $channel->name);
    }

    public function test_event_broadcasts_with_correct_name(): void
    {
        $event = new FactionLoyaltyMonitoringUpdated(1);

        $this->assertSame('faction.loyalty.monitoring.updated', $event->broadcastAs());
    }

    public function test_event_broadcasts_character_id(): void
    {
        $event = new FactionLoyaltyMonitoringUpdated(42);

        $this->assertSame(['character_id' => 42], $event->broadcastWith());
    }
}
