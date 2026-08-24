<?php

namespace Tests\Unit\Flare\Events;

use App\Flare\Events\UpdateSiteStatisticsChart;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUserSiteAccessStatistics;

class UpdateSiteStatisticsChartTest extends TestCase
{
    use CreateUser, CreateUserSiteAccessStatistics, RefreshDatabase;

    public function test_broadcast_on_returns_a_private_channel_for_the_user(): void
    {
        $user = $this->createUser();
        $this->createUserSiteAccessStatistics(['amount_signed_in' => 5, 'amount_registered' => 2]);

        $event = new UpdateSiteStatisticsChart($user);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-update-admin-site-statistics-'.$user->id, $channel->name);
    }
}
