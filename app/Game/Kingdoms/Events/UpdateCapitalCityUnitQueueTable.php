<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\Character;
use App\Game\Core\Traits\KingdomCache;

class UpdateCapitalCityUnitQueueTable implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var User $users
     */
    private User $user;

    /**
     * @var array $unitQueueData
     */
    public array $unitQueueData;

    /**
     * Create a new event instance.
     *
     * @param Character $character
     * @param Kingdom|null $kingdom
     */
    public function __construct(Character $character, Kingdom $kingdom = null) {

        $kingdomUnitQueueData = resolve(CapitalCityManagementService::class)
                ->fetchUnitQueueData($character, $kingdom);

        $this->user               = $character->user;
        $this->unitQueueData  = $kingdomUnitQueueData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array {
        return new PrivateChannel('capital-city-unit-queue-data-' . $this->user->id);
    }
}
