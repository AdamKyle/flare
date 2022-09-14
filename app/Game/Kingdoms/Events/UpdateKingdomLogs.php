<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\Character;
use App\Game\Core\Traits\KingdomCache;

class UpdateKingdomLogs implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var User $user
     */
    private User $user;

    /**
     * @var array $logs
     */
    public array $logs;

    /**
     * Create a new event instance.
     *
     * @param Character $character
     */
    public function __construct(Character $character, array $logs) {
        $this->user = $character->user;
        $this->logs = $logs;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-kingdom-logs-' . $this->user->id);
    }
}
