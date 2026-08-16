<?php

namespace Tests\Unit\Game\Automation\Exploration\Events;

use App\Game\Automation\Exploration\Events\ExplorationWarningState;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;
use Tests\Traits\CreateAutomationEventUser;

class ExplorationWarningStateTest extends TestCase
{
    use CreateAutomationEventUser;

    public function test_broadcast_on_returns_private_exploration_warning_channel(): void
    {
        $user = $this->createAutomationEventUser();

        $event = new ExplorationWarningState($user, true, [['message' => 'Danger ahead.']]);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-exploration-warning-123', $channel->name);
        $this->assertTrue($event->has_warning);
        $this->assertSame(['message' => 'Danger ahead.'], $event->warning);
    }

    public function test_warning_is_null_when_no_warnings_are_present(): void
    {
        $user = $this->createAutomationEventUser();

        $event = new ExplorationWarningState($user, false, []);

        $this->assertNull($event->warning);
    }
}
