<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\Character;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;

class UpdateLocationBasedEventGoals implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    private User $user;

    /**
     * @var bool $canSeeEventGoals
     */
    public bool $canSeeEventGoals = false;

    /**
     * Create a new event instance.
     *
     * @param User $user
     */
    public function __construct(User $user) {
        $this->user = $user->refresh();;

        $this->canSeeEventGoals = $this->canSeeEventGoals($user->character);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-location-base-event-goals-' . $this->user->id);
    }

    /**
     * Can we see the event goals tab?
     *
     * @param Character $character
     * @return bool
     */
    private function canSeeEventGoals(Character $character): bool {
        return (
            $character->map->gameMap->mapType()->isTheIcePlane() ||
            $character->map->gameMap->mapType()->isDelusionalMemories()
        );
    }
}
