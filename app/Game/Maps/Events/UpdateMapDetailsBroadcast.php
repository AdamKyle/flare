<?php

namespace App\Game\Maps\Events;

use App\Game\Core\Traits\KingdomCache;
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
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var Map $map
     */
    public $map;

    /**
     * @var array $portDetails
     */
    public $portDetails;

    /**
     * @var array $adventureDetails
     */
    public $adventureDetails;

    /**
     * @var array $kingdomDetails
     */
    public $kingdomDetails = [];

    public $updatedKingdoms = [];

    /**
     * @var User $user
     */

    private $user;

    /**
     * Create a new event instance.
     *
     * @param Map $map
     * @param User $user
     * @param array $portDetails
     * @param array $adventureDetails
     * @return void
     */
    public function __construct(Map $map, User $user, MovementService $service, bool $updateKingdoms = false)
    {

        if ($updateKingdoms) {
            $service->processArea($user->character);

            $this->updatedKingdoms = $this->getKingdoms($user->character);
        }


        $this->map              = $map;
        $this->user             = $user;
        $this->portDetails      = $service->portDetails();
        $this->adventureDetails = $service->adventureDetails();
        $this->kingdomDetails   = $service->kingdomDetails();
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
