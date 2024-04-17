<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\Kingdom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;

class UpdateKingdomTable implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array $kingdom
     */
    public array $kingdoms;

    /**
     * @var User $users
     */
    private User $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param array $kingdoms
     * @return void
     */
    public function __construct(User $user, array $kingdoms) {
        $this->user      = $user;
        $this->kingdoms  = $kingdoms;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('kingdoms-list-data-' . $this->user->id);
    }
}
