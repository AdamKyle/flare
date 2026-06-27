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

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_broadcast_as_returns_delve_status_updated(): void
    {
        $user = $this->createUser();

        $event = new DelveStatusUpdated($user->id);

        $this->assertSame('delve.status.updated', $event->broadcastAs());
    }

    public function test_broadcast_on_returns_private_user_scoped_channel(): void
    {
        $user = $this->createUser();

        $event = new DelveStatusUpdated($user->id);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-delve-status-updated-'.$user->id, $channel->name);
    }

    public function test_broadcast_with_includes_user_id(): void
    {
        $user = $this->createUser();

        $event = new DelveStatusUpdated($user->id);

        $payload = $event->broadcastWith();

        $this->assertSame($user->id, $payload['user_id']);
    }

    public function test_broadcast_with_includes_occurred_at(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00', 'UTC'));

        $user = $this->createUser();

        $event = new DelveStatusUpdated($user->id);

        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('occurred_at', $payload);
    }

    public function test_broadcast_with_does_not_include_sensitive_fields(): void
    {
        $user = $this->createUser();

        $event = new DelveStatusUpdated($user->id);

        $payload = $event->broadcastWith();

        $this->assertArrayNotHasKey('character_id', $payload);
        $this->assertArrayNotHasKey('item_data', $payload);
        $this->assertArrayNotHasKey('reward_data', $payload);
    }
}
