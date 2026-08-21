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
     * @param  int  $userId
     * @param  array  $status
     * @param  array|null  $chartPoint
     */
    public function __construct(
        private readonly int $userId,
        private readonly array $status,
        private readonly ?array $chartPoint = null,
    ) {
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
     * @return array The user id, occurred-at timestamp, status snapshot, and optional latest chart point.
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'occurred_at' => $this->occurredAt,
            'status' => $this->status,
            'chart_point' => $this->chartPoint,
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
