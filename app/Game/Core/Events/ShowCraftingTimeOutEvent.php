<?php

namespace App\Game\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Flare\Models\User;

class ShowCraftingTimeOutEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * User that sent the message
     *
     * @var \App\Flare\Models\User
     */
    public $user;

    /**
     * show the bar
     *
     * @var bool $activateBar
     */
    public $activatebar;

    /**
     * can the player craft
     *
     * @var bool $canCraft
     */
    public $canCraft;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, bool $activatebar, bool $canCraft)
    {
        $this->user        = $user;
        $this->activatebar = $activatebar;
        $this->canCraft    = $canCraft;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('show-crafting-timeout-bar-' . $this->user->id);
    }
}
