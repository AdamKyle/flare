<?php

namespace App\Flare\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;
use App\Flare\Values\SiteAccessStatisticValue;

class UpdateSiteStatisticsChart implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    public User $user;

    /**
     * @var array $registered
     */
    public array $registered = [];

    /**
     * @var array $signedin
     */
    public array $signedIn = [];

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @return void
     */
    public function __construct(User $user) {
        $this->user       = $user;
        $this->registered = SiteAccessStatisticValue::getRegistered();
        $this->signedIn   = SiteAccessStatisticValue::getSignedIn();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-admin-site-statistics-' . $this->user->id);
    }


}
