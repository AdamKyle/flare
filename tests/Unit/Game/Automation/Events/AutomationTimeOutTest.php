<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Game\Automation\Events\AutomationTimeOut;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;
use Tests\Traits\CreateAutomationEventUser;

class AutomationTimeOutTest extends TestCase
{
    use CreateAutomationEventUser;

    public function test_broadcast_on_returns_private_automation_timeout_channel(): void
    {
        $event = new AutomationTimeOut($this->createAutomationEventUser());

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automation-timeout-123', $channel->name);
    }
}
