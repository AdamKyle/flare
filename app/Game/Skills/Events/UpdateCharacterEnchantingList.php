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

class UpdateCharacterEnchantingList implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * @var Collection $availableAffixes
     */
    public Collection $availableAffixes;

    /**
     * @var array $inventory
     */
    public array $inventory;

    /**
     * @var User $user
     */
    private User $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param Collection $availableAffixes
     * @param array $inventory
     */
    public function __construct(User $user, Collection $availableAffixes, array $inventory) {
        $this->user                  = $user;
        $this->availableAffixes      = $availableAffixes;
        $this->inventory             = $inventory;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-enchanting-list-' . $this->user->id);
    }
}
