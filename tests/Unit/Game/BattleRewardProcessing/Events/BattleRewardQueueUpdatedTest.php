<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Events;

use App\Game\BattleRewardProcessing\Events\BattleRewardQueueUpdated;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Tests\TestCase;

class BattleRewardQueueUpdatedTest extends TestCase
{
    public function test_event_implements_should_broadcast_for_async_dispatch(): void
    {
        $event = new BattleRewardQueueUpdated(1, 'created');

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
    }

    public function test_event_does_not_implement_should_broadcast_now(): void
    {
        $event = new BattleRewardQueueUpdated(1, 'created');

        $this->assertNotInstanceOf(ShouldBroadcastNow::class, $event);
    }

    public function test_event_broadcasts_on_admin_monitoring_queue(): void
    {
        $event = new BattleRewardQueueUpdated(1, 'created');

        $this->assertSame('admin_monitoring', $event->broadcastQueue());
    }

    public function test_event_broadcasts_on_correct_channel(): void
    {
        $event = new BattleRewardQueueUpdated(1, 'created');

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-admin-character-reward-queue', $channel->name);
    }

    public function test_event_payload_contains_character_id_and_change(): void
    {
        $event = new BattleRewardQueueUpdated(7, 'completed');

        $this->assertSame(['character_id' => 7, 'change' => 'completed'], $event->broadcastWith());
    }
}
