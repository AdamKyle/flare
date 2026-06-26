<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Events;

use App\Game\BattleRewardProcessing\Events\BattleRewardQueueUpdated;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Tests\TestCase;

class BattleRewardQueueUpdatedTest extends TestCase
{
    public function testEventImplementsShouldBroadcastForAsyncDispatch(): void
    {
        $event = new BattleRewardQueueUpdated(1, 'created');

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
    }

    public function testEventDoesNotImplementShouldBroadcastNow(): void
    {
        $event = new BattleRewardQueueUpdated(1, 'created');

        $this->assertNotInstanceOf(ShouldBroadcastNow::class, $event);
    }

    public function testEventBroadcastsOnAdminMonitoringQueue(): void
    {
        $event = new BattleRewardQueueUpdated(1, 'created');

        $this->assertSame('admin_monitoring', $event->broadcastQueue());
    }

    public function testEventBroadcastsOnCorrectChannel(): void
    {
        $event = new BattleRewardQueueUpdated(1, 'created');

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-admin-character-reward-queue', $channel->name);
    }

    public function testEventPayloadContainsCharacterIdAndChange(): void
    {
        $event = new BattleRewardQueueUpdated(7, 'completed');

        $this->assertSame(['character_id' => 7, 'change' => 'completed'], $event->broadcastWith());
    }
}
