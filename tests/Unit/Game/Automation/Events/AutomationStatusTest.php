<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Game\Automation\Events\AutomationStatus;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;
use Tests\Traits\CreateAutomationEventUser;

class AutomationStatusTest extends TestCase
{
    use CreateAutomationEventUser;

    public function test_broadcast_on_returns_private_automation_status_channel(): void
    {
        $user = $this->createAutomationEventUser();

        $event = new AutomationStatus($user, true);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automation-status-123', $channel->name);
    }
}
