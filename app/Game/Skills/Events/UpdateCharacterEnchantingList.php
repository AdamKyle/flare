<?php

namespace App\Game\Skills\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateCharacterEnchantingList implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Collection $availableAffixes;

    public Collection $inventory;

    private User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Collection $availableAffixes, Collection $inventory)
    {
        $this->user = $user;
        $this->availableAffixes = $availableAffixes;
        $this->inventory = $inventory;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('update-enchanting-list-'.$this->user->id);
    }
}
