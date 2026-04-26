<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Flare\Models\User;
use App\Game\Automation\Events\AutomationTimeOut;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class AutomationTimeOutTest extends TestCase
{
    public function testConstructorSetsDefaultForLength(): void
    {
        $event = new AutomationTimeOut($this->user());

        $this->assertEquals(0, $event->forLength);
    }

    public function testConstructorSetsCustomForLength(): void
    {
        $event = new AutomationTimeOut($this->user(), 3600);

        $this->assertEquals(3600, $event->forLength);
    }

    public function testBroadcastOnReturnsPrivateAutomationTimeoutChannel(): void
    {
        $event = new AutomationTimeOut($this->user());

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automation-timeout-123', $channel->name);
    }

    private function user(): User
    {
        $user = new User();
        $user->id = 123;

        return $user;
    }
}