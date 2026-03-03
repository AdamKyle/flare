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

class UpdateCapitalCityBuildingQueueRequest implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    private int $userId;

    public bool $isLoading;

    public string $message;

    public string $type;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, bool $isLoading, string $message, string $type)
    {

        $this->userId = $userId;
        $this->isLoading = $isLoading;
        $this->message = $message;
        $this->type = $type;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('capital-city-building-queue-request-'.$this->userId);
    }
}
