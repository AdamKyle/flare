<?php

namespace App\Game\BattleRewardProcessing\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BattleRewardQueueUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public const REPAIRED = 'repaired';

    public function __construct(
        public readonly int $characterId,
        public readonly string $change,
    ) {}

    public function broadcastQueue(): string
    {
        return 'admin_monitoring';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('admin-character-reward-queue');
    }

    public function broadcastAs(): string
    {
        return 'battle.reward.queue.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'character_id' => $this->characterId,
            'change' => $this->change,
        ];
    }
}
