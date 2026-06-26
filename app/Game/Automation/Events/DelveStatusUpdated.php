<?php

namespace App\Game\Automation\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DelveStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private int $userId;

    private string $occurredAt;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->occurredAt = now()->toJSON();
    }

    public function broadcastAs(): string
    {
        return 'delve.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'occurred_at' => $this->occurredAt,
        ];
    }

    /**
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('delve-status-updated-' . $this->userId);
    }
}
