<?php

namespace Tests\Unit\Game\Shop\Events;

use App\Game\Shop\Events\UpdateShopEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class UpdateShopEventTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_broadcast_on_returns_private_channel_for_the_user(): void
    {
        $user = $this->createUser();

        $event = new UpdateShopEvent($user, 100, 5);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-update-shop-'.$user->id, $channel->name);
    }
}
