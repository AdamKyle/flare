<?php

namespace App\Game\Kingdoms\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Service\KingdomQueueService;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;

class UpdateKingdomQueues implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var User $users
     */
    private User $user;

    /**
     * @var array $kingdomQueues
     */
    public array $kingdomQueues;

    /**
     * @var int $kingdomId
     */
    public int $kingdomId;

    /**
     * Create a new event instance.
     *
     * @param Kingdom $kingdom
     */
    public function __construct(Kingdom $kingdom) {
        $this->user          = $kingdom->character->user;
        $this->kingdomQueues = resolve(KingdomQueueService::class)->fetchKingdomQueues($kingdom);
        $this->kingdomId     = $kingdom->id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array {
        return new PrivateChannel('refresh-kingdom-queues-' . $this->user->id);
    }
}
