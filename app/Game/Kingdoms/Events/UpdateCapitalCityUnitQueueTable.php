<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateCapitalCityUnitQueueTable implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    private User $user;

    public array $unitQueueData;

    /**
     * Create a new event instance.
     */
    public function __construct(Character $character, ?Kingdom $kingdom = null)
    {

        $kingdomUnitQueueData = resolve(CapitalCityManagementService::class)
            ->fetchUnitQueueData($character->refresh(), $kingdom);

        $this->user = $character->user;
        $this->unitQueueData = $kingdomUnitQueueData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('capital-city-unit-queue-data-'.$this->user->id);
    }
}
