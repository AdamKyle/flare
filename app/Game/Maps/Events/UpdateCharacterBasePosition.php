<?php

namespace App\Game\Maps\Events;

use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateCharacterBasePosition implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    public array $basePosition;

    private int $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, int $x, int $y, int $gameMapId)
    {
        $this->basePosition = [
            'x' => $x,
            'y' => $y,
            'game_map_id' => $gameMapId,
        ];

        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('update-character-position-'.$this->userId);
    }
}
