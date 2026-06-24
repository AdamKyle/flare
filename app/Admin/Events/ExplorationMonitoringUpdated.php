<?php

namespace App\Admin\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExplorationMonitoringUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly int $characterId) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('admin-monitoring-exploration');
    }

    public function broadcastAs(): string
    {
        return 'exploration.monitoring.updated';
    }

    public function broadcastWith(): array
    {
        return ['character_id' => $this->characterId];
    }
}
