<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Flare\Models\User;
use App\Game\Automation\Events\AutomationTimeOut;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class AutomationTimeOutTest extends TestCase
{
    public function test_constructor_sets_default_for_length(): void
    {
        $event = new AutomationTimeOut($this->user());

        $this->assertEquals(0, $event->forLength);
    }

    public function test_constructor_sets_custom_for_length(): void
    {
        $event = new AutomationTimeOut($this->user(), 3600);

        $this->assertEquals(3600, $event->forLength);
    }

    public function test_broadcast_on_returns_private_automation_timeout_channel(): void
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
