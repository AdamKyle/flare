<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Events;

use App\Game\Automation\BatchCrafting\Events\BatchCraftingStatusUpdated;
use Tests\TestCase;

class BatchCraftingStatusUpdatedTest extends TestCase
{
    public function test_broadcast_with_includes_the_user_id_status_and_null_chart_point(): void
    {
        $status = ['active' => true, 'batch' => ['id' => 1, 'chart_points' => []]];

        $event = new BatchCraftingStatusUpdated(42, $status);

        $payload = $event->broadcastWith();
        $this->assertSame(42, $payload['user_id']);
        $this->assertSame($status, $payload['status']);
        $this->assertNull($payload['chart_point']);
    }

    public function test_broadcast_with_includes_the_supplied_chart_point(): void
    {
        $chartPoint = ['occurred_at' => now()->toJSON(), 'successful' => 1, 'failed' => 0, 'gold_spent' => 10, 'gold_gained' => 0];

        $event = new BatchCraftingStatusUpdated(42, ['active' => true], $chartPoint);

        $this->assertSame($chartPoint, $event->broadcastWith()['chart_point']);
    }

    public function test_broadcast_on_uses_a_private_channel_scoped_to_the_user(): void
    {
        $event = new BatchCraftingStatusUpdated(42, []);

        $this->assertSame('private-batch-crafting-status-updated-42', $event->broadcastOn()->name);
    }

    public function test_broadcast_as_uses_the_expected_event_name(): void
    {
        $event = new BatchCraftingStatusUpdated(42, []);

        $this->assertSame('batch-crafting.status.updated', $event->broadcastAs());
    }
}
