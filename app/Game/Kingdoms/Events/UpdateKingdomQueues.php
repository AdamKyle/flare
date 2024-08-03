<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Service\KingdomQueueService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateKingdomQueues implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    private User $user;

    public array $kingdomQueues;

    public int $kingdomId;

    /**
     * Create a new event instance.
     */
    public function __construct(Kingdom $kingdom)
    {
        $this->user = $kingdom->character->user;
        $this->kingdomQueues = resolve(KingdomQueueService::class)->fetchKingdomQueues($kingdom);
        $this->kingdomId = $kingdom->id;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('refresh-kingdom-queues-'.$this->user->id);
    }
}
