<?php

namespace App\Game\Character\CharacterSheet\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateCharacterBaseDetailsBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array
     */
    public array $characterSheet;

    /**
     * @var User
     */
    private User $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $characterSheet, User $user)
    {
        $this->characterSheet = $characterSheet;
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|PrivateChannel
     */
    public function broadcastOn(): Channel|PrivateChannel
    {
        return new PrivateChannel('update-character-base-details-'.$this->user->id);
    }
}
