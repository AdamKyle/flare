<?php

namespace App\Game\BatchCrafting\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BatchCraftingStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private string $occurredAt;

    public function __construct(private readonly int $userId)
    {
        $this->occurredAt = now()->toJSON();
    }

    public function broadcastAs(): string
    {
        return 'batch-crafting.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'occurred_at' => $this->occurredAt,
        ];
    }

    public function broadcastOn()
    {
        return new PrivateChannel('batch-crafting-status-updated-'.$this->userId);
    }
}
