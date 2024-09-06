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

        $siteAccessStatistics = resolve(SiteAccessStatisticValue::class);

        $loginDetails = $siteAccessStatistics->setAttribute('amount_signed_in')->setDaysPast(0);
        $registrationDetails = $siteAccessStatistics->setAttribute('amount_registered')->setDaysPast(0);

        $this->registered = $registrationDetails->getRegistered();
        $this->signedIn = $loginDetails->getSignedIn();
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
