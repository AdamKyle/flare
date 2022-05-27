<?php

namespace App\Game\Maps\Events;

use App\Game\Maps\Services\LocationService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;

class UpdateMapBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var array $mapDetails
     */
    public $mapDetails;

    /**
     * @var User $user
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     */
    public function __construct(User $user) {
        $this->mapDetails = resolve(LocationService::class)->getLocationData($user->character);
        $this->user       = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-map-plane-' . $this->user->id);
    }
}
