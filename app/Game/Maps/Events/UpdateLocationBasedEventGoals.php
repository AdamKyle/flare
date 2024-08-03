<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateLocationBasedEventGoals implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;

    public bool $canSeeEventGoals = false;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user->refresh();

        $this->canSeeEventGoals = $this->canSeeEventGoals($user->character);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-location-base-event-goals-'.$this->user->id);
    }

    /**
     * Can we see the event goals tab?
     */
    private function canSeeEventGoals(Character $character): bool
    {
        return
            $character->map->gameMap->mapType()->isTheIcePlane() ||
            $character->map->gameMap->mapType()->isDelusionalMemories();
    }
}
