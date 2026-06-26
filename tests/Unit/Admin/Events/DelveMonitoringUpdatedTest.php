<?php

namespace Tests\Unit\Admin\Events;

use App\Admin\Events\DelveMonitoringUpdated;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Tests\TestCase;

class DelveMonitoringUpdatedTest extends TestCase
{
    public function testEventImplementsShouldBroadcastForAsyncDispatch(): void
    {
        $event = new DelveMonitoringUpdated(1);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
    }

    public function testEventDoesNotImplementShouldBroadcastNow(): void
    {
        $event = new DelveMonitoringUpdated(1);

        $this->assertNotInstanceOf(ShouldBroadcastNow::class, $event);
    }

    public function testEventBroadcastsOnAdminMonitoringQueue(): void
    {
        $event = new DelveMonitoringUpdated(1);

        $this->assertSame('admin_monitoring', $event->broadcastQueue());
    }

    public function testEventBroadcastsOnCorrectChannel(): void
    {
        $event = new DelveMonitoringUpdated(1);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-admin-monitoring-delve', $channel->name);
    }

    public function testEventBroadcastsWithCorrectName(): void
    {
        $event = new DelveMonitoringUpdated(1);

        $this->assertSame('delve.monitoring.updated', $event->broadcastAs());
    }

    public function testEventBroadcastsCharacterId(): void
    {
        $event = new DelveMonitoringUpdated(42);

        $this->assertSame(['character_id' => 42], $event->broadcastWith());
    }
}
