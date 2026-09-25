<?php

namespace App\Game\BattleRewardProcessing\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BattleRewardProgressionUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $userId
     * @param int $requestId
     * @param ?int $level
     * @param ?int $xp
     * @param ?int $xpNext
     * @param bool $complete
     */
    public function __construct(
        private readonly int $userId,
        public readonly int $requestId,
        public readonly ?int $level,
        public readonly ?int $xp,
        public readonly ?int $xpNext,
        public readonly bool $complete = false,
    ) {}

    /**
     * Return the private channel of the user whose historical XP presentation is being streamed.
     *
     * @return PrivateChannel
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('battle-reward-progression-'.$this->userId);
    }
}
