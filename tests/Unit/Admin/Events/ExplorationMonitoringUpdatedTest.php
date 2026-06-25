<?php

namespace Tests\Unit\Admin\Events;

use App\Admin\Events\ExplorationMonitoringUpdated;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Tests\TestCase;

class ExplorationMonitoringUpdatedTest extends TestCase
{
    public function testEventImplementsShouldBroadcastForAsyncDispatch(): void
    {
        $event = new ExplorationMonitoringUpdated(1);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
    }

    public function testEventDoesNotImplementShouldBroadcastNow(): void
    {
        $event = new ExplorationMonitoringUpdated(1);

        $this->assertNotInstanceOf(ShouldBroadcastNow::class, $event);
    }

    public function testEventBroadcastsOnAdminMonitoringQueue(): void
    {
        $event = new ExplorationMonitoringUpdated(1);

        $this->assertSame('admin_monitoring', $event->broadcastQueue());
    }

    public function testEventBroadcastsOnCorrectChannel(): void
    {
        $event = new ExplorationMonitoringUpdated(1);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-admin-monitoring-exploration', $channel->name);
    }

    public function testEventBroadcastsWithCorrectName(): void
    {
        $event = new ExplorationMonitoringUpdated(1);

        $this->assertSame('exploration.monitoring.updated', $event->broadcastAs());
    }

    public function testEventBroadcastsCharacterId(): void
    {
        $event = new ExplorationMonitoringUpdated(42);

        $this->assertSame(['character_id' => 42], $event->broadcastWith());
    }
}
