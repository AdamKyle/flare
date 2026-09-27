<?php

namespace App\Game\Automation\Delve\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DelveStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $userId
     * @param array $status
     */
    public function __construct(
        private readonly int $userId,
        private readonly array $status,
    ) {}

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'delve.status.updated';
    }

    /**
     * Get the Delve status snapshot to broadcast with the event.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'status' => $this->status,
        ];
    }

    /**
     * Get the private channel the Delve status is broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('delve-status-updated-'.$this->userId);
    }
}
