<?php

namespace App\Game\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UpdateNotificationsBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Collection $notifications
     */
    public $notifications;

    /**
     * @param User $user
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param Collection $notifications
     * @param User $user
     * @return void
     */
    public function __construct(Collection $notifications, User $user)
    {
        $this->notifications = $notifications;
        $this->user          = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-notifications-' . $this->user->id);
    }
}
