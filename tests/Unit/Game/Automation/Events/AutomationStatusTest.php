<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Game\Automation\Events\AutomationStatus;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;
use Tests\Traits\CreateAutomationEventUser;

class AutomationStatusTest extends TestCase
{
    use CreateAutomationEventUser;

    public function test_constructor_sets_is_running_to_true(): void
    {
        $user = $this->createAutomationEventUser();

        $event = new AutomationStatus($user, true);

        $this->assertTrue($event->isRunning);
    }

    public function test_constructor_sets_is_running_to_false(): void
    {
        $user = $this->createAutomationEventUser();

        $event = new AutomationStatus($user, false);

        $this->assertFalse($event->isRunning);
    }

    public function test_broadcast_on_returns_private_automation_status_channel(): void
    {
        $user = $this->createAutomationEventUser();

        $event = new AutomationStatus($user, true);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automation-status-123', $channel->name);
    }
}
