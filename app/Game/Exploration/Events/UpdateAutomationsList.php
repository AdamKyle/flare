<?php

namespace App\Game\Exploration\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Flare\Models\User;

class UpdateAutomationsList implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    public $user;

    /**
     * @var Collection $automations
     */
    public $automations;

    /**
     * @var bool $activatebar
     */
    public $activatebar;

    /**
     * @param User $user
     * @param Collection $automations
     */
    public function __construct(User $user, Collection $automations)
    {
        $this->user        = $user;
        $this->automations = $automations;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('automations-list-' . $this->user->id);
    }
}
