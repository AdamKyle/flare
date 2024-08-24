<?php

namespace App\Flare\Events;

use App\Flare\Models\User;
use App\Flare\Values\SiteAccessStatisticValue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateSiteStatisticsChart implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;

    public array $registered = [];

    public array $signedIn = [];

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->registered = SiteAccessStatisticValue::getRegistered();
        $this->signedIn = SiteAccessStatisticValue::getSignedIn();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-admin-site-statistics-'.$this->user->id);
    }
}
