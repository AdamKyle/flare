<?php

namespace App\Game\Maps\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Game\Core\Traits\KingdomCache;
Use App\Flare\Models\User;

class UpdateCharacterBasePosition implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var array $basePosition
     */
    public array $basePosition;

    /**
     * @var int $userId
     */
    private int $userId;

    /**
     * Create a new event instance.
     *
     * @param int $userId
     * @param int $x
     * @param int $y
     * @param int $gameMapId
     */
    public function __construct(int $userId, int $x, int $y, int $gameMapId) {
        $this->basePosition = [
            'x'           => $x,
            'y'           => $y,
            'game_map_id' => $gameMapId,
        ];

        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array {
        return new PrivateChannel('update-character-position-' . $this->userId);
    }
}
