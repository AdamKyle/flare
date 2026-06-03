<?php

namespace Tests\Unit\Game\Messages\Events;

use App\Game\Messages\Events\ServerMessageEvent;
use Carbon\Carbon;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class ServerMessageEventTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testConstructorSetsPayloadValues(): void
    {
        $user = $this->createUser();

        $event = new ServerMessageEvent($user, 'Test server message', 456);

        $this->assertEquals('Test server message', $event->message);
        $this->assertEquals(456, $event->id);
    }

    public function testConstructorSetsTimeStamp(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 14:45:00', 'UTC'));

        $user = $this->createUser();

        $event = new ServerMessageEvent($user, 'Test server message');

        $this->assertEquals(now()->toJSON(), $event->timeStamp);
    }

    public function testBroadcastOnReturnsPrivateServerMessageChannel(): void
    {
        $user = $this->createUser();

        $event = new ServerMessageEvent($user, 'Test server message');

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-server-message-'.$user->id, $channel->name);
    }
}