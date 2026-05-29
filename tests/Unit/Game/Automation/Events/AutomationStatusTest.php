<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Flare\Models\User;
use App\Game\Automation\Events\AutomationStatus;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class AutomationStatusTest extends TestCase
{
    public function testConstructorSetsIsRunningToTrue(): void
    {
        $user = $this->user();

        $event = new AutomationStatus($user, true);

        $this->assertTrue($event->isRunning);
    }

    public function testConstructorSetsIsRunningToFalse(): void
    {
        $user = $this->user();

        $event = new AutomationStatus($user, false);

        $this->assertFalse($event->isRunning);
    }

    public function testBroadcastOnReturnsPrivateAutomationStatusChannel(): void
    {
        $user = $this->user();

        $event = new AutomationStatus($user, true);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automation-status-123', $channel->name);
    }

    private function user(): User
    {
        $user = new User();
        $user->id = 123;

        return $user;
    }
}