<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\Map;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Maps\Services\Common\CanPlayerMassEmbezzle;
use App\Game\Maps\Services\LocationService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateMapDetailsBroadcast implements ShouldBroadcastNow
{
    use CanPlayerMassEmbezzle, Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    public array $map_data;

    private User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Map $map, User $user, LocationService $service)
    {
        $this->map_data = $service->getLocationData($user->character);

        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-map-'.$this->user->id);
    }
}
