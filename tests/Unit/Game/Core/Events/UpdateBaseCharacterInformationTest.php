<?php

namespace Tests\Unit\Game\Core\Events;

use App\Game\Core\Events\UpdateBaseCharacterInformation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class UpdateBaseCharacterInformationTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function testEventImplementsShouldBroadcastNotShouldBroadcastNow(): void
    {
        $user = $this->createUser();
        $event = new UpdateBaseCharacterInformation($user, []);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertNotInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcastNow::class, $event);
    }

    public function testBroadcastQueueReturnsCharacterBroadcasts(): void
    {
        $user = $this->createUser();
        $event = new UpdateBaseCharacterInformation($user, []);

        $this->assertEquals('character_broadcasts', $event->broadcastQueue());
    }

    public function testBroadcastChannelIncludesUserId(): void
    {
        $user = $this->createUser();
        $event = new UpdateBaseCharacterInformation($user, []);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertStringContainsString((string) $user->id, $channel->name);
    }
}
