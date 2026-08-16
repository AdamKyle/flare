<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Events;

use App\Game\Automation\BatchCrafting\Events\BatchCraftingStatusUpdated;
use Tests\TestCase;

class BatchCraftingStatusUpdatedTest extends TestCase
{
    public function test_broadcast_with_includes_the_user_id(): void
    {
        $event = new BatchCraftingStatusUpdated(42);

        $this->assertSame(42, $event->broadcastWith()['user_id']);
    }

    public function test_broadcast_on_uses_a_private_channel_scoped_to_the_user(): void
    {
        $event = new BatchCraftingStatusUpdated(42);

        $this->assertSame('private-batch-crafting-status-updated-42', $event->broadcastOn()->name);
    }

    public function test_broadcast_as_uses_the_expected_event_name(): void
    {
        $event = new BatchCraftingStatusUpdated(42);

        $this->assertSame('batch-crafting.status.updated', $event->broadcastAs());
    }
}
