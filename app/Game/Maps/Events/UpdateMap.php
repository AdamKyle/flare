<?php

namespace App\Game\Maps\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;
use App\Game\Maps\Services\LocationService;

class UpdateMap implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array $map_locations
     */
    public array $map_locations;

    /**
     * @var User $user
     */
    private User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $character = $user->character->refresh();
        $this->map_locations = resolve(LocationService::class)->fetchLocationData($character);
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('update-plane-' . $this->user->id);
    }
}
