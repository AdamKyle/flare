<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Game\Automation\Events\DelveStatusUpdated;
use Carbon\Carbon;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class DelveStatusUpdatedTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testBroadcastAsReturnsDelveStatusUpdated(): void
    {
        $user = $this->createUser();

        $event = new DelveStatusUpdated($user->id);

        $this->assertSame('delve.status.updated', $event->broadcastAs());
    }

    public function testBroadcastOnReturnsPrivateUserScopedChannel(): void
    {
        $user = $this->createUser();

        $event = new DelveStatusUpdated($user->id);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-delve-status-updated-' . $user->id, $channel->name);
    }

    public function testBroadcastWithIncludesUserId(): void
    {
        $user = $this->createUser();

        $event = new DelveStatusUpdated($user->id);

        $payload = $event->broadcastWith();

        $this->assertSame($user->id, $payload['user_id']);
    }

    public function testBroadcastWithIncludesOccurredAt(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00', 'UTC'));

        $user = $this->createUser();

        $event = new DelveStatusUpdated($user->id);

        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('occurred_at', $payload);
    }

    public function testBroadcastWithDoesNotIncludeSensitiveFields(): void
    {
        $user = $this->createUser();

        $event = new DelveStatusUpdated($user->id);

        $payload = $event->broadcastWith();

        $this->assertArrayNotHasKey('character_id', $payload);
        $this->assertArrayNotHasKey('item_data', $payload);
        $this->assertArrayNotHasKey('reward_data', $payload);
    }
}
