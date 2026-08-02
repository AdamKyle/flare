<?php

namespace Tests\Unit\Game\Core\Events;

use App\Game\Core\Events\UpdateBaseCharacterInformation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class UpdateBaseCharacterInformationTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_event_implements_should_broadcast_not_should_broadcast_now(): void
    {
        $user = $this->createUser();
        $event = new UpdateBaseCharacterInformation($user, ['data' => []]);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertNotInstanceOf(ShouldBroadcastNow::class, $event);
    }

    public function test_broadcast_queue_returns_character_broadcasts(): void
    {
        $user = $this->createUser();
        $event = new UpdateBaseCharacterInformation($user, ['data' => []]);

        $this->assertEquals('character_broadcasts', $event->broadcastQueue());
    }

    public function test_broadcast_channel_includes_user_id(): void
    {
        $user = $this->createUser();
        $event = new UpdateBaseCharacterInformation($user, ['data' => []]);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertStringContainsString((string) $user->id, $channel->name);
    }
}
