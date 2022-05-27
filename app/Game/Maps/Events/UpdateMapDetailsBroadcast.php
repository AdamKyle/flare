<?php

namespace App\Game\Maps\Events;

use App\Flare\Models\Character;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Maps\Services\Common\CanPlayerMassEmbezzle;
use App\Game\Maps\Services\LocationService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;
use App\Flare\Models\Map;
use App\Game\Maps\Services\MovementService;

class UpdateMapDetailsBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache, CanPlayerMassEmbezzle;

    /**
     * @var array
     */
    public array $map_data;

    /**
     * @var User $user
     */
    private $user;

    /**
     * Create a new event instance.
     *
     * @param Map $map
     * @param User $user
     * @param MovementService $service
     * @param bool $updateKingdoms
     */
    public function __construct(Map $map, User $user, LocationService $service,)
    {
        $this->map_data = $service->getLocationData($user->character);
        $this->user     = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-map-' . $this->user->id);
    }
}
