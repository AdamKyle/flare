<?php

namespace App\Game\Automation\BatchCrafting\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BatchCraftingStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private string $occurredAt;

    /**
     * @param  int  $userId  The owning user's identifier.
     */
    public function __construct(private readonly int $userId)
    {
        $this->occurredAt = now()->toJSON();
    }

    /**
     * Return the broadcast event name.
     *
     * @return string The broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'batch-crafting.status.updated';
    }

    /**
     * Return the broadcast payload for this event.
     *
     * @return array The user id and occurred-at timestamp.
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'occurred_at' => $this->occurredAt,
        ];
    }

    /**
     * Return the private channel this event broadcasts on.
     *
     * @return PrivateChannel The user's Batch Crafting status channel.
     */
    public function broadcastOn()
    {
        return new PrivateChannel('batch-crafting-status-updated-'.$this->userId);
    }
}
