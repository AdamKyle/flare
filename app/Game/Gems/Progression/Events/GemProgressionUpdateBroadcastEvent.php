<?php

namespace App\Game\Gems\Progression\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Compact authoritative Gem progression/active-Scroll summary update for the
 * Character's owning user, broadcast on the existing global game-data
 * private-channel pattern so mounted Gem Progress/Character/Map surfaces
 * update live without a page refresh or polling.
 */
class GemProgressionUpdateBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;

    public array $gemProgress;

    public function __construct(array $gemProgress, User $user)
    {
        $this->gemProgress = $gemProgress;
        $this->user = $user;
    }

    /**
     * Get the channel the event should broadcast on.
     */
    public function broadcastOn(): Channel|PrivateChannel
    {
        return new PrivateChannel('update-gem-progression-'.$this->user->id);
    }
}
